<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        Ticket #{{ $ticket->id }} â€“ {{ $ticket->subject }}
    </x-slot>

    <x-slot:headerFiles>
        @vite(['resources/scss/light/plugins/editors/quill/quill.snow.scss'])
    </x-slot>

    <div class="row layout-top-spacing">
        <div class="col-lg-8 offset-lg-2">
            <div class="widget p-4 border rounded">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3">
                    <div>
                        <h4 class="mb-2">{{ $ticket->subject }}</h4>
                        <div class="text-muted">
                            <i class="bi bi-person-circle me-1"></i>
                            {{ $ticket->user->name }} &lt;{{ $ticket->user->email }}&gt;
                        </div>
                    </div>
                
                    <div class="text-end">
                        <div class="mb-1">
                            <span class="badge {{ $ticket->status === 'closed' ? 'bg-danger' : ($ticket->status === 'pending' ? 'bg-primary' : 'bg-primary') }}">
                                {{ ucfirst($ticket->status) }}
                            </span>
                            <span class="badge {{ $ticket->priority === 'high' ? 'bg-danger' : ($ticket->priority === 'medium' ? 'bg-primary' : 'bg-secondary') }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </div>
                        <small class="text-muted d-block">Assigned To: {{ $ticket->assignedTo->name ?? 'â€”' }}</small>
                        <small class="text-muted">Created At: {{ $ticket->created_at->format('d M Y H:i') }}</small>
                    </div>
                </div>
                
                <hr>

                <div class="mb-4">
                    <h6 class="fw-bold">Message:</h6>
                    <div class="p-3 bg-light rounded border">
                        {!! $ticket->message !!}
                        
                        @if($ticket->attachments->count())
                            <div class="mt-4 mb-4">
                                <h6 class="fw-bold">Attachments:</h6>
                                <ul class="list-unstyled">
                                    @foreach($ticket->attachments as $file)
                                        <li>
                                            <a href="{{ Storage::url($file->filepath) }}" target="_blank">
                                                ðŸ“Ž {{ $file->filename }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    
                </div>

                <hr>

                <h5 class="mb-3">Replies</h5>
                @foreach($ticket->replies as $reply)
                    <div class="border rounded p-3 mb-3">
                        <div class="mb-2">
                            <span class="fw-bold">{{ $reply->user->name }}</span>
                            <small class="text-muted ms-2">{{ $reply->created_at->format('d M Y H:i') }}</small>
                        </div>
                        <div>{!! $reply->message !!}</div>
                
                        @if($reply->attachments->count())
                            <div class="mt-2">
                                <small class="text-muted">Attachments:</small>
                                <ul class="list-unstyled">
                                    @foreach($reply->attachments as $file)
                                        <li>
                                            <a href="{{ Storage::url($file->filepath) }}" target="_blank">ðŸ“Ž {{ $file->filename }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach


                <hr>

                @if($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('user.tickets.reply', $ticket->id) }}" enctype="multipart/form-data" onsubmit="return syncQuillReply()">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reply Message</label>
                            <div id="quill-reply-editor" style="height: 150px;"></div>
                            <input type="hidden" name="message" id="quill-reply-input">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attachments (optional)</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Send Reply</button>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('close-ticket-form').submit();" class="btn btn-outline-danger">Close Ticket</a>
                        </div>
                    </form>

                    <form id="close-ticket-form" method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" style="display: none;">
                        @csrf
                    </form>
                @else
                    <div class="alert alert-info mt-4">This ticket is closed.</div>
                @endif
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        <script src="{{ asset('plugins/editors/quill/quill.js') }}"></script>
        <script>
            const quillReply = new Quill('#quill-reply-editor', {
                theme: 'snow',
                placeholder: 'Type your reply...'
            });

            function syncQuillReply() {
                const html = quillReply.root.innerHTML;
                document.getElementById('quill-reply-input').value = html;
                return true;
            }
        </script>
    </x-slot>
</x-base-layout>
