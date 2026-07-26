const chat = document.querySelector('[data-chat]');

if (chat) {
    const form = chat.querySelector('form');
    const input = chat.querySelector('textarea');
    const messages = chat.querySelector('[data-messages]');
    const emptyState = chat.querySelector('[data-empty]');
    const submitButton = chat.querySelector('[data-submit]');
    const newChatButton = chat.querySelector('[data-new-chat]');
    const errorBox = chat.querySelector('[data-error]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const storageKey = 'openai-chat-state';
    let state = loadState();

    function loadState() {
        try {
            return JSON.parse(localStorage.getItem(storageKey)) ?? { responseId: null, messages: [] };
        } catch {
            return { responseId: null, messages: [] };
        }
    }

    function saveState() {
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    function addMessage(role, content, persist = true) {
        const template = document.querySelector(`[data-${role}-template]`);
        const node = template.content.cloneNode(true);
        node.querySelector('[data-content]').textContent = content;
        messages.appendChild(node);
        emptyState.hidden = true;

        if (persist) {
            state.messages.push({ role, content });
            saveState();
        }

        messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
    }

    function setLoading(isLoading) {
        input.disabled = isLoading;
        submitButton.disabled = isLoading;
        submitButton.querySelector('[data-send-label]').hidden = isLoading;
        submitButton.querySelector('[data-loading-label]').hidden = !isLoading;
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 160)}px`;
    }

    state.messages.forEach(({ role, content }) => addMessage(role, content, false));
    input.addEventListener('input', resizeInput);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();

        if (!message) {
            return;
        }

        errorBox.hidden = true;
        addMessage('user', message);
        input.value = '';
        resizeInput();
        setLoading(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message,
                    previous_response_id: state.responseId,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message ?? 'ارسال پیام ناموفق بود.');
            }

            state.responseId = data.id;
            addMessage('assistant', data.message);
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        } finally {
            setLoading(false);
            input.focus();
        }
    });

    newChatButton.addEventListener('click', () => {
        state = { responseId: null, messages: [] };
        saveState();
        messages.querySelectorAll('[data-message]').forEach((message) => message.remove());
        emptyState.hidden = false;
        errorBox.hidden = true;
        input.focus();
    });
}
