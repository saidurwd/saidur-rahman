<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApplyMessageAction;
use App\Enums\MessageStatus;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageBulkRequest;
use App\Http\Requests\Admin\MessageStatusRequest;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    use ManagesModule;

    private const FOLDERS = ['inbox', 'unread', 'starred', 'spam', 'all'];

    protected function modelClass(): string
    {
        return Message::class;
    }

    protected function routeBase(): string
    {
        return 'admin.messages';
    }

    protected function moduleLabel(): string
    {
        return 'Messages';
    }

    protected function labelAttribute(): string
    {
        return 'name';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Message::class);
        $folder = $this->folder($request);

        $query = Message::query();
        ApplyMessageAction::filter($query, $folder, $request->string('q')->squish()->toString());

        return view('admin.messages.index', [
            'messages' => $query->latest()->latest('id')->paginate(20)->withQueryString(),
            'folder' => $folder,
            'counts' => [
                'inbox' => Message::inbox()->count(),
                'unread' => Message::unread()->count(),
                'starred' => Message::inbox()->where('starred', true)->count(),
                'spam' => Message::query()->where('status', MessageStatus::Spam)->count(),
            ],
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function show(Message $message, ApplyMessageAction $actions): View
    {
        $this->authorize('view', $message);

        if ($message->getAttribute('status') === MessageStatus::New) {
            $actions->setStatus($message, MessageStatus::Read);
        }

        return view('admin.messages.show', ['message' => $message->refresh()]);
    }

    public function star(Message $message): RedirectResponse
    {
        $this->authorize('update', $message);
        $message->update(['starred' => ! $message->starred]);

        return back();
    }

    public function status(MessageStatusRequest $request, Message $message, ApplyMessageAction $actions): RedirectResponse
    {
        $status = MessageStatus::from($request->validated('status'));
        $actions->setStatus($message, $status);

        return back()->with('status', 'Marked as '.strtolower($status->name).'.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $this->authorize('delete', $message);
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Message moved to Trash.');
    }

    public function bulk(MessageBulkRequest $request, ApplyMessageAction $actions): RedirectResponse
    {
        $count = $actions($request->validated('action'), $request->validated('ids'));

        return back()->with('status', "{$count} message(s) updated.");
    }

    /** CSV of the messages in the current folder and search, for backups or a spreadsheet. */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Message::class);

        $query = Message::query();
        ApplyMessageAction::filter($query, $this->folder($request), $request->string('q')->squish()->toString());

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Received', 'Status', 'Starred', 'Name', 'Email', 'Company', 'Subject', 'Message', 'IP']);

            $query->latest()->latest('id')->lazyById(200)->each(function (Message $m) use ($out) {
                fputcsv($out, array_map($this->safeCell(...), [
                    $m->id, $m->created_at?->toDateTimeString(), $m->getAttribute('status')->value, $m->starred ? 'yes' : 'no',
                    $m->name, $m->email, $m->company, $m->subject, $m->body, $m->ip,
                ]));
            });

            fclose($out);
        }, 'messages-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function folder(Request $request): string
    {
        $folder = $request->string('folder')->toString();

        return in_array($folder, self::FOLDERS, true) ? $folder : 'inbox';
    }

    /** Stops a spreadsheet from running a cell that starts like a formula (CSV injection). */
    private function safeCell(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
