<?php
namespace App\Repositories\SIAP;

use App\Models\SIAP\Comment;

class CommentRepository
{
    public function CreateComment($Query, $uid)
    {
        $C = Comment::withTrashed()->where(array_merge($Query, [
            'created_by' => $uid,
        ]));
        if (!is_object($C->first())) {
            Comment::create(array_merge($Query, [
                'created_by' => $uid,
                'comment' => null,
            ]));
            return;
        }
        if (is_object(Comment::onlyTrashed()->where(array_merge($Query, [
            'created_by' => $uid,
        ]))->first())) {
            $C->update([
                'comment' => null,
            ]);
            $C->restore();
            return;
        }
    }
}
