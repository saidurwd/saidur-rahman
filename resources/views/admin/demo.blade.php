@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => route('admin.dashboard'), 'Components' => null]])

@section('title', 'Components')

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <x-admin.card title="Form fields">
                <x-admin.form.input name="demo_title" label="Input" help="Helper text" value="Sample" />
                <x-admin.form.select name="demo_status" label="Select" :options="['draft' => 'Draft', 'published' => 'Published']" value="draft" placeholder="Choose…" />
                <x-admin.form.textarea name="demo_summary" label="Textarea" />
                <x-admin.form.editor name="demo_body" label="Editor" :rows="6" />
                <x-admin.form.toggle name="demo_featured" label="Featured" :checked="true" />
                <x-admin.form.image-upload name="demo_image" label="Image upload" alt-name="demo_image_alt" />
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card title="Status badges">
                @foreach (['draft', 'scheduled', 'published', 'archived', 'new', 'replied', 'spam'] as $status)
                    <x-admin.status-badge :status="$status" />
                @endforeach
            </x-admin.card>
            <x-admin.card title="Table and delete button" flush>
                <x-admin.table :headers="['Title', 'Status', '']">
                    <tr>
                        <td>Example row</td>
                        <td><x-admin.status-badge status="published" /></td>
                        <td class="text-end"><x-admin.delete-button action="#" /></td>
                    </tr>
                </x-admin.table>
            </x-admin.card>
            <x-admin.card title="Empty table" flush>
                <x-admin.table :headers="['Title']" empty="No items yet." />
            </x-admin.card>
        </div>
    </div>
@endsection
