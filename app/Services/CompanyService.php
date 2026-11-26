<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LibraryItem;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Http\JsonResponse;

class CompanyService
{
    public function create(User $user, array $data): Company
    {
        
            $data = Company::create( [
                'title'        => $data['title'] ?? 'Add title',
                'type'        => $data['type'] ?? 'text',
                'user_id'     => $user->id,
                'content'     => $data['content']?? null,
                'notes' =>   $data['notes']?? null,
                'model'       => $data['model']??null,
            ]);


        return $data;
    }

    public function update(User $user, int $id, array $data): LibraryItem
    {
        return DB::transaction(function () use ($user, $id, $data) {
            $item = $user->libraryItems()->findOrFail($id);
            if (!filled($data['content'] ?? null)) {
            unset($data['content']);
            }
            $item->update($data);

            return $item->refresh();
        });
    }

    public function delete(User $user, int $id): void
    {
        // Scoped to the user, so someone else's item returns 404
        $user->libraryItems()->findOrFail($id)->delete();
    }

    public function storefile(LibraryItem $item, UploadedFile $file): ?JsonResponse
{
    $extension = strtolower($file->guessExtension() ?: $file->extension());
    $name = Str::uuid()->toString() . ($extension ? '.' . $extension : '');

    $path = Storage::disk('public')->putFileAs(
        'libraryAttachments',
        $file,
        $name
    );

    if ($path === false) {
        return ResponseFacade::error('The file could not be stored.', 500);
    }

    $item->update([
       
        'attachment' => 'libraryAttachments/'.$name,
        'type' => 'file',
        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
        'size' => $file->getSize(),
    ]);

    return null;
}
}