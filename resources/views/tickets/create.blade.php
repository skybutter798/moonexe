<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Create Ticket
    </x-slot>
    
    <x-slot:headerFiles>
        @vite(['resources/scss/light/plugins/editors/quill/quill.snow.scss'])
    </x-slot>

    <div class="container py-4">
        <h4 class="mb-4">Submit a New Support Ticket</h4>

        <form method="POST" action="{{ route('user.tickets.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" rows="5" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Attachments (optional)</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('user.tickets.index') }}" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
    
    <x-slot:footerFiles>
        <script src="{{ asset('plugins/editors/quill/quill.js') }}"></script>
        <script>
            const quill = new Quill('#quill-editor', {
                theme: 'snow',
                placeholder: 'Describe the issue or request in detail...'
            });

            function syncQuill() {
                const html = quill.root.innerHTML;
                document.getElementById('hidden-message').value = html;
                return true;
            }
        </script>
    </x-slot>
</x-base-layout>
