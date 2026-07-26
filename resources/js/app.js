const chat = document.querySelector('[data-chat]');

if (chat) {
    const form = chat.querySelector('form');
    const input = chat.querySelector('textarea');
    const messages = chat.querySelector('[data-messages]');
    const emptyState = chat.querySelector('[data-empty]');
    const submitButton = chat.querySelector('[data-submit]');
    const newChatButtons = chat.querySelectorAll('[data-new-chat]');
    const themeButtons = chat.querySelectorAll('[data-theme-toggle]');
    const modelSelect = chat.querySelector('[data-model]');
    const fileInput = chat.querySelector('[data-files]');
    const filePreview = chat.querySelector('[data-file-preview]');
    const imageModeButton = chat.querySelector('[data-image-mode]');
    const errorBox = chat.querySelector('[data-error]');
    const history = chat.querySelector('[data-history]');
    const historyEmpty = chat.querySelector('[data-history-empty]');
    const currentTitle = chat.querySelector('[data-current-title]');
    const sidebar = chat.querySelector('[data-sidebar]');
    const sidebarBackdrop = chat.querySelector('[data-sidebar-backdrop]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const conversationUrl = chat.dataset.conversationUrl;
    let currentConversationId = null;
    let selectedFiles = [];
    let generateImage = false;

    function updateThemeButtons(isDark) {
        themeButtons.forEach((button) => {
            button.setAttribute('aria-label', isDark ? 'فعال‌کردن حالت روشن' : 'فعال‌کردن حالت تاریک');
            button.setAttribute('title', isDark ? 'حالت روشن' : 'حالت تاریک');
        });
    }

    function setTheme(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';

        try {
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        } catch (_) {}

        updateThemeButtons(isDark);
    }

    updateThemeButtons(document.documentElement.classList.contains('dark'));

    function appendText(container, text) {
        container.appendChild(document.createTextNode(text));
    }

    function isSafeLink(url) {
        try {
            return ['http:', 'https:', 'mailto:'].includes(new URL(url).protocol);
        } catch (_) {
            return false;
        }
    }

    function renderInline(container, text) {
        let plainText = '';

        const flushPlainText = () => {
            if (plainText) {
                appendText(container, plainText);
                plainText = '';
            }
        };

        for (let index = 0; index < text.length;) {
            if (text[index] === '\\' && index + 1 < text.length) {
                plainText += text[index + 1];
                index += 2;
                continue;
            }

            if (text[index] === '`') {
                const closingIndex = text.indexOf('`', index + 1);

                if (closingIndex !== -1) {
                    flushPlainText();
                    const code = document.createElement('code');
                    code.textContent = text.slice(index + 1, closingIndex);
                    container.appendChild(code);
                    index = closingIndex + 1;
                    continue;
                }
            }

            if (text[index] === '[') {
                const labelEnd = text.indexOf('](', index + 1);
                const linkEnd = labelEnd === -1 ? -1 : text.indexOf(')', labelEnd + 2);

                if (labelEnd !== -1 && linkEnd !== -1) {
                    const label = text.slice(index + 1, labelEnd);
                    const url = text.slice(labelEnd + 2, linkEnd).trim();

                    flushPlainText();

                    if (isSafeLink(url)) {
                        const link = document.createElement('a');
                        link.href = url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        renderInline(link, label);
                        container.appendChild(link);
                    } else {
                        appendText(container, label);
                    }

                    index = linkEnd + 1;
                    continue;
                }
            }

            const marker = text.slice(index, index + 2);

            if (marker === '**' || marker === '__') {
                const closingIndex = text.indexOf(marker, index + 2);

                if (closingIndex !== -1) {
                    flushPlainText();
                    const strong = document.createElement('strong');
                    renderInline(strong, text.slice(index + 2, closingIndex));
                    container.appendChild(strong);
                    index = closingIndex + 2;
                    continue;
                }
            }

            if (text[index] === '*' || text[index] === '_') {
                const closingIndex = text.indexOf(text[index], index + 1);

                if (closingIndex !== -1) {
                    flushPlainText();
                    const emphasis = document.createElement('em');
                    renderInline(emphasis, text.slice(index + 1, closingIndex));
                    container.appendChild(emphasis);
                    index = closingIndex + 1;
                    continue;
                }
            }

            plainText += text[index];
            index += 1;
        }

        flushPlainText();
    }

    function splitTableRow(line) {
        const trimmedLine = line.trim().replace(/^\|/, '').replace(/\|$/, '');
        const cells = [];
        let cell = '';
        let isEscaped = false;

        for (const character of trimmedLine) {
            if (isEscaped) {
                cell += character;
                isEscaped = false;
            } else if (character === '\\') {
                isEscaped = true;
            } else if (character === '|') {
                cells.push(cell.trim());
                cell = '';
            } else {
                cell += character;
            }
        }

        cells.push(cell.trim());

        return cells;
    }

    function isTableDivider(line) {
        const cells = splitTableRow(line);

        return cells.length > 0 && cells.every((cell) => /^:?-{3,}:?$/.test(cell));
    }

    function isBlockStart(lines, index) {
        const line = lines[index];

        return /^\s*(```|~~~)/.test(line)
            || /^\s{0,3}#{1,6}\s+/.test(line)
            || /^\s{0,3}([-*_])(?:\s*\1){2,}\s*$/.test(line)
            || /^\s*>/.test(line)
            || /^\s*(?:[-+*]|\d+[.)])\s+/.test(line)
            || (index + 1 < lines.length && line.includes('|') && isTableDivider(lines[index + 1]));
    }

    function renderMarkdown(container, markdown) {
        container.replaceChildren();
        const lines = String(markdown ?? '').replace(/\r\n?/g, '\n').split('\n');

        for (let index = 0; index < lines.length;) {
            const line = lines[index];

            if (!line.trim()) {
                index += 1;
                continue;
            }

            const fenceMatch = line.match(/^\s*(```|~~~)\s*([\w+-]*)\s*$/);

            if (fenceMatch) {
                const codeLines = [];
                index += 1;

                while (index < lines.length && !new RegExp(`^\\s*${fenceMatch[1]}\\s*$`).test(lines[index])) {
                    codeLines.push(lines[index]);
                    index += 1;
                }

                index += index < lines.length ? 1 : 0;
                const pre = document.createElement('pre');
                const code = document.createElement('code');
                code.textContent = codeLines.join('\n');

                if (fenceMatch[2]) {
                    code.dataset.language = fenceMatch[2];
                }

                pre.appendChild(code);
                container.appendChild(pre);
                continue;
            }

            const headingMatch = line.match(/^\s{0,3}(#{1,6})\s+(.+)$/);

            if (headingMatch) {
                const heading = document.createElement(`h${headingMatch[1].length}`);
                renderInline(heading, headingMatch[2].replace(/\s+#+\s*$/, ''));
                container.appendChild(heading);
                index += 1;
                continue;
            }

            if (/^\s{0,3}([-*_])(?:\s*\1){2,}\s*$/.test(line)) {
                container.appendChild(document.createElement('hr'));
                index += 1;
                continue;
            }

            if (/^\s*>/.test(line)) {
                const quoteLines = [];

                while (index < lines.length && /^\s*>/.test(lines[index])) {
                    quoteLines.push(lines[index].replace(/^\s*>\s?/, ''));
                    index += 1;
                }

                const blockquote = document.createElement('blockquote');
                renderMarkdown(blockquote, quoteLines.join('\n'));
                container.appendChild(blockquote);
                continue;
            }

            const listMatch = line.match(/^\s*(?:([-+*])|(\d+)[.)])\s+(.+)$/);

            if (listMatch) {
                const isOrdered = Boolean(listMatch[2]);
                const list = document.createElement(isOrdered ? 'ol' : 'ul');

                if (isOrdered && listMatch[2] !== '1') {
                    list.start = Number(listMatch[2]);
                }

                while (index < lines.length) {
                    const itemMatch = lines[index].match(/^\s*(?:([-+*])|(\d+)[.)])\s+(.+)$/);

                    if (!itemMatch || Boolean(itemMatch[2]) !== isOrdered) {
                        break;
                    }

                    const item = document.createElement('li');
                    renderInline(item, itemMatch[3]);
                    list.appendChild(item);
                    index += 1;
                }

                container.appendChild(list);
                continue;
            }

            if (index + 1 < lines.length && line.includes('|') && isTableDivider(lines[index + 1])) {
                const headers = splitTableRow(line);
                const alignments = splitTableRow(lines[index + 1]).map((cell) => {
                    if (cell.startsWith(':') && cell.endsWith(':')) {
                        return 'center';
                    }

                    return cell.endsWith(':') ? 'right' : 'left';
                });
                const wrapper = document.createElement('div');
                const table = document.createElement('table');
                const head = document.createElement('thead');
                const headRow = document.createElement('tr');

                headers.forEach((headerText, column) => {
                    const header = document.createElement('th');
                    header.style.textAlign = alignments[column] ?? 'left';
                    renderInline(header, headerText);
                    headRow.appendChild(header);
                });

                head.appendChild(headRow);
                table.appendChild(head);
                index += 2;
                const body = document.createElement('tbody');

                while (index < lines.length && lines[index].trim() && lines[index].includes('|')) {
                    const row = document.createElement('tr');

                    splitTableRow(lines[index]).forEach((cellText, column) => {
                        const cell = document.createElement('td');
                        cell.style.textAlign = alignments[column] ?? 'left';
                        renderInline(cell, cellText);
                        row.appendChild(cell);
                    });

                    body.appendChild(row);
                    index += 1;
                }

                table.appendChild(body);
                wrapper.className = 'markdown-table-wrapper';
                wrapper.appendChild(table);
                container.appendChild(wrapper);
                continue;
            }

            const paragraphLines = [line];
            index += 1;

            while (index < lines.length && lines[index].trim() && !isBlockStart(lines, index)) {
                paragraphLines.push(lines[index]);
                index += 1;
            }

            const paragraph = document.createElement('p');
            paragraphLines.forEach((paragraphLine, lineIndex) => {
                if (lineIndex > 0) {
                    paragraph.appendChild(document.createElement('br'));
                }

                renderInline(paragraph, paragraphLine);
            });
            container.appendChild(paragraph);
        }
    }

    function addMessage(role, content, attachments = []) {
        const template = document.querySelector(`[data-${role}-template]`);
        const fragment = template.content.cloneNode(true);
        const message = fragment.querySelector('[data-message]');
        const contentContainer = message.querySelector('[data-content]');

        if (role === 'assistant') {
            renderMarkdown(contentContainer, content);
        } else {
            contentContainer.textContent = content;
        }

        const attachmentContainer = message.querySelector('[data-attachments]');
        attachments.forEach((attachment) => {
            const attachmentTemplate = document.querySelector(`[data-${attachment.kind}-attachment-template]`);
            const attachmentFragment = attachmentTemplate.content.cloneNode(true);
            const link = attachmentFragment.querySelector('[data-attachment-link]');
            link.href = attachment.url;

            if (attachment.kind === 'image') {
                const image = attachmentFragment.querySelector('[data-attachment-image]');
                image.src = attachment.url;
                image.alt = attachment.name;
            } else {
                attachmentFragment.querySelector('[data-attachment-name]').textContent = attachment.name;
            }

            attachmentContainer.appendChild(attachmentFragment);
        });

        messages.appendChild(fragment);
        emptyState.hidden = true;
        messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });

        return message;
    }

    function clearMessages() {
        messages.querySelectorAll('[data-message]').forEach((message) => message.remove());
        emptyState.hidden = false;
    }

    function setLoading(isLoading) {
        input.disabled = isLoading;
        submitButton.disabled = isLoading;
        modelSelect.disabled = isLoading;
        fileInput.disabled = isLoading;
        imageModeButton.disabled = isLoading;
        submitButton.querySelector('[data-send-label]').hidden = isLoading;
        submitButton.querySelector('[data-loading-label]').hidden = !isLoading;
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 160)}px`;
    }

    function setImageMode(enabled) {
        generateImage = enabled;
        imageModeButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        input.placeholder = enabled ? 'تصویری که می‌خواهی را توصیف کن...' : 'پیامت را بنویس...';
    }

    function renderFilePreview() {
        filePreview.replaceChildren();

        selectedFiles.forEach((file, index) => {
            const fragment = document.querySelector('[data-file-preview-template]').content.cloneNode(true);
            const item = fragment.querySelector('[data-preview-item]');
            item.querySelector('[data-preview-name]').textContent = file.name;
            item.querySelector('[data-remove-file]').dataset.fileIndex = index;
            filePreview.appendChild(fragment);
        });

        filePreview.hidden = selectedFiles.length === 0;
    }

    function clearSelectedFiles() {
        selectedFiles = [];
        fileInput.value = '';
        renderFilePreview();
    }

    function closeSidebar() {
        sidebar.classList.add('translate-x-full');
        sidebarBackdrop.hidden = true;
    }

    function selectHistoryItem(conversationId) {
        history.querySelectorAll('[data-conversation-item]').forEach((item) => {
            item.setAttribute('aria-current', item.dataset.conversationId === conversationId ? 'true' : 'false');
        });
    }

    function upsertHistoryItem(conversation) {
        let item = history.querySelector(`[data-conversation-id="${conversation.id}"]`);

        if (!item) {
            const fragment = document.querySelector('[data-conversation-template]').content.cloneNode(true);
            item = fragment.querySelector('[data-conversation-item]');
            item.dataset.conversationId = conversation.id;
            history.prepend(fragment);
        } else {
            history.prepend(item);
        }

        item.querySelector('[data-history-title]').textContent = conversation.title;
        historyEmpty.hidden = true;
        selectHistoryItem(conversation.id);
    }

    async function loadConversation(conversationId) {
        errorBox.hidden = true;
        setLoading(true);

        try {
            const response = await fetch(conversationUrl.replace('__CONVERSATION__', conversationId), {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message ?? 'بارگذاری گفتگو ناموفق بود.');
            }

            clearMessages();
            data.messages.forEach(({ role, content, attachments }) => addMessage(role, content, attachments));
            currentConversationId = data.conversation.id;
            currentTitle.textContent = data.conversation.title;
            modelSelect.value = data.conversation.model;
            selectHistoryItem(currentConversationId);
            closeSidebar();
        } catch (error) {
            showError(error.message);
        } finally {
            setLoading(false);
            input.focus();
        }
    }

    function startNewConversation() {
        currentConversationId = null;
        currentTitle.textContent = 'گفت‌وگوی تازه';
        selectHistoryItem(null);
        clearMessages();
        clearSelectedFiles();
        setImageMode(false);
        errorBox.hidden = true;
        closeSidebar();
        input.focus();
    }

    input.addEventListener('input', resizeInput);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    history.addEventListener('click', (event) => {
        const item = event.target.closest('[data-conversation-item]');

        if (item) {
            loadConversation(item.dataset.conversationId);
        }
    });

    fileInput.addEventListener('change', () => {
        selectedFiles = [...fileInput.files].slice(0, 5);
        renderFilePreview();
    });

    filePreview.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-file]');

        if (removeButton) {
            selectedFiles.splice(Number(removeButton.dataset.fileIndex), 1);
            renderFilePreview();
        }
    });

    imageModeButton.addEventListener('click', () => setImageMode(!generateImage));
    modelSelect.addEventListener('change', () => {
        try {
            localStorage.setItem('chat-model', modelSelect.value);
        } catch (_) {}
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();

        if (!message && selectedFiles.length === 0) {
            return;
        }

        errorBox.hidden = true;
        const optimisticAttachments = selectedFiles.map((file) => ({
            kind: file.type.startsWith('image/') ? 'image' : 'file',
            name: file.name,
            url: URL.createObjectURL(file),
        }));
        const pendingMessage = addMessage('user', message, optimisticAttachments);
        input.value = '';
        resizeInput();
        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('message', message);
            formData.append('model', modelSelect.value);
            formData.append('mode', generateImage ? 'image' : 'chat');

            if (currentConversationId) {
                formData.append('conversation_id', currentConversationId);
            }

            selectedFiles.forEach((file) => formData.append('files[]', file));

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message ?? 'ارسال پیام ناموفق بود.');
            }

            currentConversationId = data.conversation.id;
            currentTitle.textContent = data.conversation.title;
            modelSelect.value = data.conversation.model;
            pendingMessage.remove();
            addMessage(data.user_message.role, data.user_message.content, data.user_message.attachments);
            addMessage(data.assistant_message.role, data.assistant_message.content, data.assistant_message.attachments);
            upsertHistoryItem(data.conversation);
            clearSelectedFiles();
            setImageMode(false);
        } catch (error) {
            pendingMessage.remove();
            input.value = message;
            resizeInput();

            if (!messages.querySelector('[data-message]')) {
                emptyState.hidden = false;
            }

            showError(error.message);
        } finally {
            optimisticAttachments.forEach((attachment) => URL.revokeObjectURL(attachment.url));
            setLoading(false);
            input.focus();
        }
    });

    newChatButtons.forEach((button) => button.addEventListener('click', startNewConversation));
    themeButtons.forEach((button) => {
        button.addEventListener('click', () => setTheme(!document.documentElement.classList.contains('dark')));
    });
    chat.querySelector('[data-sidebar-open]').addEventListener('click', () => {
        sidebar.classList.remove('translate-x-full');
        sidebarBackdrop.hidden = false;
    });
    chat.querySelector('[data-sidebar-close]').addEventListener('click', closeSidebar);
    sidebarBackdrop.addEventListener('click', closeSidebar);

    try {
        const savedModel = localStorage.getItem('chat-model');

        if (savedModel && modelSelect.querySelector(`option[value="${savedModel}"]`)) {
            modelSelect.value = savedModel;
        }
    } catch (_) {}
}
