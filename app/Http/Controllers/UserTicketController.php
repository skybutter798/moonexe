<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Auth;

class UserTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('replies')
            ->where('user_id', Auth::id());

        if ($request->has('status') && in_array($request->status, ['open', 'pending', 'closed'])) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(20);
        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);

        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open',
            'priority' => 'medium',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'filename' => $file->getClientOriginalName(),
                    'filepath' => $path,
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('user.tickets.show', $ticket->id)->with('success', 'Ticket submitted.');
    }

    public function show($id)
    {
        $ticket = Ticket::with(['replies.user', 'attachments'])
                        ->where('user_id', Auth::id())
                        ->findOrFail($id);

        return view('tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                    'filename' => $file->getClientOriginalName(),
                    'filepath' => $path,
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return back()->with('success', 'Reply sent.');
    }

    public function close($id)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);
        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        return back()->with('success', 'Ticket closed.');
    }
}
