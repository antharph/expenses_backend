<?php

namespace App\Contracts;

interface DeletesFirebaseUsers
{
    public function delete(string $firebaseUid): void;
}
