<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SavePost;
use App\Enums\PostStatus;
use App\Http\Controllers\Admin\Concerns\ManagesModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    use ManagesModule;

    protected function modelClass(): string
    {
        return Post::class;
    }

    protected function routeBase(): string
    {
        return 'admin.posts';
    }

    protected function moduleLabel(): string
    {
        return 'Articles';
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);

        return view('admin.posts.index', [
            'records' => Post::query()
                ->with('category')
                ->search($request->string('q')->toString())
                ->withStatus($request->string('status')->toString())
                ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
                ->orderByRaw('COALESCE(published_at, created_at) DESC')
                ->paginate(15)
                ->withQueryString(),
            'statuses' => PostStatus::cases(),
            'categories' => Category::ordered()->pluck('name', 'id'),
            'trashedCount' => $this->trashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Post::class);

        return view('admin.posts.form', $this->formData(new Post(['status' => PostStatus::Draft])));
    }

    public function store(SavePostRequest $request, SavePost $save): RedirectResponse
    {
        $post = $save(new Post, $request->safe()->all());

        return redirect()->route('admin.posts.edit', $post)->with('status', 'Article created.');
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        return view('admin.posts.form', $this->formData($post));
    }

    public function update(SavePostRequest $request, Post $post, SavePost $save): RedirectResponse
    {
        $save($post, $request->safe()->all());

        return redirect()->route('admin.posts.edit', $post)->with('status', 'Article saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Article moved to Trash.');
    }

    /** @return array<string, mixed> */
    private function formData(Post $post): array
    {
        return [
            'record' => $post->loadMissing('tags'),
            'categories' => Category::ordered()->pluck('name', 'id')->all(),
            'knownTags' => Tag::query()->orderBy('name')->pluck('name'),
        ];
    }
}
