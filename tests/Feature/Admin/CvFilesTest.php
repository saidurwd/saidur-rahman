<?php

use App\Models\CvFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    signInAsAdmin();
    Storage::fake(CvFile::DISK);
});

function pdf(string $name = 'cv.pdf', int $kb = 50): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".str_repeat('x', $kb * 1024)."\n%%EOF");
}

it('keeps guests out', function () {
    auth()->logout();

    $this->flushSession()->get('/admin/cv-files')->assertRedirect('/admin/login');
    $this->flushSession()->get('/admin/cv-files/1/download')->assertRedirect('/admin/login');
});

it('uploads a PDF to the private disk under a random name', function () {
    $this->post('/admin/cv-files', ['label' => 'IT Leadership', 'file' => pdf('My Private Name.pdf'), 'is_active' => 1])
        ->assertRedirect('/admin/cv-files');

    $cv = CvFile::firstOrFail();
    expect($cv->label)->toBe('IT Leadership')
        ->and($cv->is_active)->toBeTrue()
        ->and($cv->file_path)->toMatch('#^cv/[A-Za-z0-9]{40}\.pdf$#')
        ->and($cv->file_path)->not->toContain('Private');
    Storage::disk(CvFile::DISK)->assertExists($cv->file_path);
});

it('rejects non-PDF, oversized and missing files', function () {
    $this->post('/admin/cv-files', ['label' => 'X', 'file' => UploadedFile::fake()->image('cv.png')])->assertSessionHasErrors('file');
    $this->post('/admin/cv-files', ['label' => 'X', 'file' => pdf('big.pdf', 11000)])->assertSessionHasErrors('file');
    $this->post('/admin/cv-files', ['label' => 'X'])->assertSessionHasErrors('file');
    $this->post('/admin/cv-files', ['label' => '', 'file' => pdf()])->assertSessionHasErrors('label');

    expect(CvFile::count())->toBe(0);
});

it('keeps one active version per label', function () {
    $this->post('/admin/cv-files', ['label' => 'Engineering', 'file' => pdf(), 'is_active' => 1]);
    $this->post('/admin/cv-files', ['label' => 'Engineering', 'file' => pdf(), 'is_active' => 1]);
    $this->post('/admin/cv-files', ['label' => 'IT Leadership', 'file' => pdf(), 'is_active' => 1]);

    expect(CvFile::where('label', 'Engineering')->where('is_active', true)->count())->toBe(1)
        ->and(CvFile::where('label', 'IT Leadership')->where('is_active', true)->count())->toBe(1);
});

it('sets a version active', function () {
    $old = CvFile::factory()->create(['label' => 'Engineering', 'is_active' => true]);
    $new = CvFile::factory()->create(['label' => 'Engineering', 'is_active' => false]);

    $this->post("/admin/cv-files/{$new->id}/activate")->assertRedirect()->assertSessionHas('status');

    expect($old->fresh()->is_active)->toBeFalse()->and($new->fresh()->is_active)->toBeTrue();
});

it('replaces the file on edit and deletes the old one', function () {
    $this->post('/admin/cv-files', ['label' => 'Engineering', 'file' => pdf()]);
    $cv = CvFile::firstOrFail();
    $oldPath = $cv->file_path;

    $this->put("/admin/cv-files/{$cv->id}", ['label' => 'Engineering', 'file' => pdf(), 'is_active' => 1])->assertRedirect('/admin/cv-files');

    expect($cv->fresh()->file_path)->not->toBe($oldPath);
    Storage::disk(CvFile::DISK)->assertMissing($oldPath);
    Storage::disk(CvFile::DISK)->assertExists($cv->fresh()->file_path);
});

it('keeps the file when only the label changes', function () {
    $this->post('/admin/cv-files', ['label' => 'Engineering', 'file' => pdf()]);
    $cv = CvFile::firstOrFail();

    $this->put("/admin/cv-files/{$cv->id}", ['label' => 'Renamed'])->assertRedirect();

    expect($cv->fresh()->label)->toBe('Renamed')->and($cv->fresh()->file_path)->toBe($cv->file_path);
});

it('lists CV files with their download counts', function () {
    CvFile::factory()->create(['label' => 'Engineering', 'downloads' => 1234]);

    $this->get('/admin/cv-files')->assertOk()->assertSee('Engineering')->assertSee('1,234');
});

it('streams a download for the admin', function () {
    $this->post('/admin/cv-files', ['label' => 'IT Leadership', 'file' => pdf()]);
    $cv = CvFile::firstOrFail();

    $this->get("/admin/cv-files/{$cv->id}/download")->assertOk()->assertDownload('it-leadership-cv.pdf');
    $this->get('/admin/cv-files/999/download')->assertNotFound();
});

it('removes the stored file only when force deleted', function () {
    $this->post('/admin/cv-files', ['label' => 'Engineering', 'file' => pdf()]);
    $cv = CvFile::firstOrFail();
    $path = $cv->file_path;

    $this->delete("/admin/cv-files/{$cv->id}")->assertRedirect('/admin/cv-files');
    Storage::disk(CvFile::DISK)->assertExists($path);
    $this->get('/admin/cv-files/trash')->assertSee('Engineering');

    $this->post("/admin/cv-files/{$cv->id}/restore")->assertRedirect();
    $this->delete("/admin/cv-files/{$cv->id}")->assertRedirect();
    $this->delete("/admin/cv-files/{$cv->id}/force")->assertRedirect();

    Storage::disk(CvFile::DISK)->assertMissing($path);
    expect(CvFile::withTrashed()->count())->toBe(0);
});
