<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Chat
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">

                <div id="chat-log" class="mb-4 space-y-3 max-h-96 overflow-y-auto"></div>

                <form id="chat-form" class="flex gap-2">
                    @csrf
                    <input
                        type="text"
                        id="message"
                        placeholder="Ask a question about your documents..."
                        class="flex-1 border-gray-300 rounded"
                        required
                    >
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded border border-indigo-800">
                        Send
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('chat-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const input = document.getElementById('message');
            const log = document.getElementById('chat-log');
            const message = input.value;

            log.innerHTML += `<div class="text-right"><span class="inline-block bg-gray-100 px-3 py-2 rounded">${message}</span></div>`;
            input.value = '';
            input.disabled = true;

            const response = await fetch('{{ route('chat.ask') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                },
                body: JSON.stringify({ message }),
            });

            const data = await response.json();

            log.innerHTML += `<div class="text-left"><span class="inline-block bg-indigo-50 px-3 py-2 rounded">${data.answer}</span></div>`;
            input.disabled = false;
            input.focus();
            log.scrollTop = log.scrollHeight;
        });
    </script>
</x-app-layout>