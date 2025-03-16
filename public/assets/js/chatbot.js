class AIChatbot {
    constructor(config) {
        this.websiteId = config.websiteId;
        this.apiEndpoint = config.apiEndpoint;
        this.position = config.position || 'bottom-right';
        this.primaryColor = config.primaryColor || '#007bff';
        this.createChatWidget();
        this.visitorId = this.generateVisitorId();
    }

    createChatWidget() {
        // Create chat container
        const container = document.createElement('div');
        container.id = 'ai-chatbot-container';
        container.style.cssText = `
            position: fixed;
            ${this.position.includes('bottom') ? 'bottom: 20px;' : 'top: 20px;'}
            ${this.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            z-index: 999999;
        `;

        // Create chat header
        const header = document.createElement('div');
        header.style.cssText = `
            padding: 15px;
            background: ${this.primaryColor};
            color: white;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        header.innerHTML = `
            <span>Chat Assistant</span>
            <span class="close-btn" style="cursor: pointer;">×</span>
        `;

        // Create chat messages container
        const messagesContainer = document.createElement('div');
        messagesContainer.style.cssText = `
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        `;
        messagesContainer.id = 'ai-chatbot-messages';

        // Create input container
        const inputContainer = document.createElement('div');
        inputContainer.style.cssText = `
            padding: 15px;
            border-top: 1px solid #dee2e6;
            display: flex;
            background: white;
            border-radius: 0 0 10px 10px;
        `;

        // Create input field
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Type your message...';
        input.style.cssText = `
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-right: 8px;
        `;

        // Create send button
        const sendButton = document.createElement('button');
        sendButton.innerHTML = 'Send';
        sendButton.style.cssText = `
            padding: 8px 16px;
            background: ${this.primaryColor};
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        `;

        // Create toggle button
        const toggleButton = document.createElement('div');
        toggleButton.id = 'ai-chatbot-toggle';
        toggleButton.style.cssText = `
            position: fixed;
            ${this.position.includes('bottom') ? 'bottom: 20px;' : 'top: 20px;'}
            ${this.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
            width: 60px;
            height: 60px;
            background: ${this.primaryColor};
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 999999;
        `;
        toggleButton.innerHTML = '💬';

        // Append elements
        inputContainer.appendChild(input);
        inputContainer.appendChild(sendButton);
        container.appendChild(header);
        container.appendChild(messagesContainer);
        container.appendChild(inputContainer);
        document.body.appendChild(container);
        document.body.appendChild(toggleButton);

        // Add event listeners
        toggleButton.addEventListener('click', () => this.toggleChat());
        header.querySelector('.close-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleChat(false);
        });
        sendButton.addEventListener('click', () => this.sendMessage(input.value));
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage(input.value);
            }
        });
    }

    toggleChat(show = null) {
        const container = document.getElementById('ai-chatbot-container');
        const toggleButton = document.getElementById('ai-chatbot-toggle');
        const isVisible = show !== null ? show : container.style.display === 'none';
        
        container.style.display = isVisible ? 'flex' : 'none';
        toggleButton.style.display = isVisible ? 'none' : 'flex';
    }

    async sendMessage(message) {
        if (!message.trim()) return;

        const input = document.querySelector('#ai-chatbot-container input');
        const messagesContainer = document.getElementById('ai-chatbot-messages');
        
        // Clear input
        input.value = '';

        // Add user message
        this.addMessage('user', message);

        try {
            // Show typing indicator
            this.addTypingIndicator();

            // Send message to server
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    website_id: this.websiteId,
                    visitor_id: this.visitorId,
                    message: message
                })
            });

            const data = await response.json();

            // Remove typing indicator
            this.removeTypingIndicator();

            if (data.status === 'success') {
                this.addMessage('assistant', data.response);
            } else {
                this.addMessage('assistant', 'Sorry, I encountered an error. Please try again later.');
            }

        } catch (error) {
            this.removeTypingIndicator();
            this.addMessage('assistant', 'Sorry, I encountered an error. Please try again later.');
        }
    }

    addMessage(role, content) {
        const messagesContainer = document.getElementById('ai-chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 80%;
            ${role === 'user' ? `
                background: ${this.primaryColor};
                color: white;
                margin-left: auto;
                border-bottom-right-radius: 5px;
            ` : `
                background: white;
                border: 1px solid #dee2e6;
                margin-right: auto;
                border-bottom-left-radius: 5px;
            `}
        `;
        messageDiv.textContent = content;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    addTypingIndicator() {
        const messagesContainer = document.getElementById('ai-chatbot-messages');
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typing-indicator';
        typingDiv.style.cssText = `
            margin-bottom: 10px;
            padding: 8px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            border-bottom-left-radius: 5px;
            max-width: 60px;
            display: flex;
            align-items: center;
        `;
        typingDiv.innerHTML = '<div class="typing-dots">...</div>';
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    removeTypingIndicator() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    generateVisitorId() {
        return 'visitor_' + Math.random().toString(36).substr(2, 9);
    }
}

// Usage:
// const chatbot = new AIChatbot({
//     websiteId: 'your-website-id',
//     apiEndpoint: 'https://your-api-endpoint/chat',
//     position: 'bottom-right',
//     primaryColor: '#007bff'
// }); 