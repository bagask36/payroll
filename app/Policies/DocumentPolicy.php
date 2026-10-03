<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view the document metadata.
     */
    public function view(User $user, Document $document): bool
    {
        if ($document->isOwner($user)) {
            return true;
        }

        return $user->hasPermission('document.view_all');
    }

    /**
     * Determine whether the user can generate temporary download URLs.
     */
    public function download(User $user, Document $document): bool
    {
        if ($document->isOwner($user)) {
            return true;
        }

        return $user->hasPermission('document.download');
    }

    /**
     * Determine whether the user can upload documents.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('document.upload_own') || $user->hasPermission('document.upload_all');
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('document.delete');
    }
}
