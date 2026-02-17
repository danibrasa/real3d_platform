@props(['project', 'enabled' => false])

@if($enabled)
<div x-data="chatbotWidget()" x-cloak class="fixed bottom-6 right-6 z-50" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    {{-- Chat button --}}
    <button x-show="!open" @click="open = true; $nextTick(() => scrollToBottom())"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="scale-75 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            class="w-14 h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </button>

    {{-- Chat panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="w-[360px] max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden flex flex-col"
         style="height: min(520px, calc(100vh - 100px));">

        {{-- Header --}}
        <div class="bg-emerald-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div>
                <div class="font-semibold text-sm">{{ $project->name }}</div>
                <div class="text-emerald-100 text-xs">{{ app()->getLocale() === 'en' ? 'AI Assistant' : 'Asistente IA' }}</div>
            </div>
            <button @click="open = false" class="text-emerald-100 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div x-ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3" style="scroll-behavior: smooth;">
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                            ? 'bg-emerald-600 text-white rounded-2xl rounded-br-md px-4 py-2 max-w-[80%] text-sm'
                            : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-md px-4 py-2 max-w-[80%] text-sm'"
                         x-html="formatMessage(msg.content)">
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="isLoading" class="flex justify-start">
                <div class="bg-gray-100 rounded-2xl rounded-bl-md px-4 py-3">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                    </div>
                </div>
            </div>

            {{-- Lead capture form --}}
            <div x-show="showLeadForm && !leadCaptured" class="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                <p class="text-sm font-medium text-emerald-800 mb-2" x-text="locale === 'en' ? 'Want an advisor to contact you?' : 'Quieres que un asesor te contacte?'"></p>
                <div class="space-y-2">
                    <input x-model="leadForm.name" type="text" :placeholder="locale === 'en' ? 'Your name' : 'Tu nombre'"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                    <input x-model="leadForm.email" type="email" :placeholder="locale === 'en' ? 'Email' : 'Email'"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                    <input x-model="leadForm.phone" type="tel" :placeholder="locale === 'en' ? 'Phone (optional)' : 'Telefono (opcional)'"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                    <div class="flex gap-2">
                        <button @click="submitLead()" :disabled="!leadForm.name || !leadForm.email || leadSubmitting"
                                class="flex-1 bg-emerald-600 text-white text-sm py-1.5 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition">
                            <span x-show="!leadSubmitting" x-text="locale === 'en' ? 'Send' : 'Enviar'"></span>
                            <span x-show="leadSubmitting">...</span>
                        </button>
                        <button @click="showLeadForm = false"
                                class="px-3 text-sm text-gray-500 hover:text-gray-700" x-text="locale === 'en' ? 'Later' : 'Despues'">
                        </button>
                    </div>
                </div>
            </div>

            {{-- Lead captured confirmation --}}
            <div x-show="leadCaptured && justCaptured" class="bg-green-50 border border-green-200 rounded-xl p-3 text-center">
                <p class="text-sm text-green-700" x-text="locale === 'en' ? 'Thanks! An advisor will contact you soon.' : 'Gracias! Un asesor te contactara pronto.'"></p>
            </div>
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-200 px-3 py-2 flex-shrink-0">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <input x-model="input" type="text" :disabled="isLoading || limitReached"
                       :placeholder="limitReached
                           ? (locale === 'en' ? 'Message limit reached' : 'Limite de mensajes alcanzado')
                           : (locale === 'en' ? 'Type your question...' : 'Escribe tu pregunta...')"
                       class="flex-1 text-sm border border-gray-300 rounded-full px-4 py-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-gray-100"
                       maxlength="500">
                <button type="submit" :disabled="!input.trim() || isLoading || limitReached"
                        class="w-9 h-9 bg-emerald-600 text-white rounded-full flex items-center justify-center hover:bg-emerald-700 disabled:opacity-50 transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function chatbotWidget() {
    return {
        open: false,
        messages: [],
        input: '',
        isLoading: false,
        sessionId: null,
        locale: '{{ app()->getLocale() }}',
        slug: '{{ $project->slug }}',
        leadCaptured: false,
        justCaptured: false,
        showLeadForm: false,
        leadSubmitting: false,
        limitReached: false,
        leadForm: { name: '', email: '', phone: '' },
        messagesCount: 0,
        leadAfter: {{ config('chatbot.lead_capture_after_messages', 3) }},

        init() {
            // Try to restore session
            const key = 'chatbot_' + this.slug;
            const saved = sessionStorage.getItem(key);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.sessionId = data.sessionId;
                    this.messages = data.messages || [];
                    this.leadCaptured = data.leadCaptured || false;
                    this.messagesCount = data.messagesCount || 0;
                    return;
                } catch (e) {}
            }
            this.sessionId = crypto.randomUUID();
            this.addBotMessage(this.locale === 'en'
                ? `Hi! I'm the virtual assistant for {{ $project->name }}. How can I help you? Ask me about units, prices, availability, or anything about the project.`
                : `Hola! Soy el asistente virtual de {{ $project->name }}. ¿En que puedo ayudarte? Preguntame sobre unidades, precios, disponibilidad o cualquier aspecto del proyecto.`
            );
        },

        addBotMessage(content) {
            this.messages.push({ role: 'assistant', content });
            this.saveSession();
        },

        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.isLoading || this.limitReached) return;

            this.messages.push({ role: 'user', content: text });
            this.input = '';
            this.isLoading = true;
            this.$nextTick(() => this.scrollToBottom());

            // Track event
            if (typeof window.trackEvent === 'function') {
                window.trackEvent('chatbot_message_sent', { messages_count: this.messagesCount });
            }

            try {
                const res = await fetch(`/api/projects/${this.slug}/chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ message: text, session_id: this.sessionId }),
                });

                const data = await res.json();

                if (data.error) {
                    this.addBotMessage(data.error);
                } else {
                    this.addBotMessage(data.message);
                    this.leadCaptured = data.lead_captured || this.leadCaptured;
                    this.messagesCount = data.messages_count || this.messagesCount;
                    this.limitReached = data.limit_reached || false;

                    // Show lead form after N messages
                    if (data.suggest_lead && !this.leadCaptured && !this.showLeadForm) {
                        this.showLeadForm = true;
                    }
                }
            } catch (e) {
                this.addBotMessage(this.locale === 'en'
                    ? 'Connection error. Please try again.'
                    : 'Error de conexion. Intenta de nuevo.');
            }

            this.isLoading = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        async submitLead() {
            if (!this.leadForm.name || !this.leadForm.email) return;
            this.leadSubmitting = true;

            try {
                const res = await fetch(`/api/projects/${this.slug}/chat/lead`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        name: this.leadForm.name,
                        email: this.leadForm.email,
                        phone: this.leadForm.phone || null,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    this.leadCaptured = true;
                    this.justCaptured = true;
                    this.showLeadForm = false;
                    this.saveSession();

                    if (typeof window.trackEvent === 'function') {
                        window.trackEvent('chatbot_lead_captured');
                    }

                    setTimeout(() => this.justCaptured = false, 5000);
                }
            } catch (e) {}

            this.leadSubmitting = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        saveSession() {
            const key = 'chatbot_' + this.slug;
            sessionStorage.setItem(key, JSON.stringify({
                sessionId: this.sessionId,
                messages: this.messages.slice(-30), // Keep last 30 messages
                leadCaptured: this.leadCaptured,
                messagesCount: this.messagesCount,
            }));
        },

        formatMessage(text) {
            // Basic markdown-like formatting
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
        },
    };
}
</script>
@endif
