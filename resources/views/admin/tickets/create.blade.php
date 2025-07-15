<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Create Support Ticket
    </x-slot>

    <x-slot:headerFiles>
        @vite([
            'resources/scss/light/assets/components/modal.scss',
            'resources/scss/light/plugins/editors/quill/quill.snow.scss'
        ])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-lg-8 offset-lg-2">
            <div class="widget p-4 border rounded">
                <h4 class="mb-4">Create New Support Ticket</h4>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data" onsubmit="return syncQuill()">
                    @csrf

                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <div id="quill-editor" style="height: 200px;"></div>
                        <input type="hidden" name="message" id="hidden-message">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachments (optional)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Submit Ticket</button>
                    </div>
                </form>
            </div>
        </div>
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
