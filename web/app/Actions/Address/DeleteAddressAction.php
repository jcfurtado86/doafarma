<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAddressAction
{
    /**
     * Execute the action.
     *
     * @throws ValidationException if the owner must keep this address or it is referenced by an existing record
     */
    public function execute(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            // Locking the owner serializes concurrent deletes, so two requests cannot
            // each see two addresses and together remove a doctor's last one
            $owner = User::whereKey($address->user_id)->lockForUpdate()->firstOrFail();

            if (! $owner->canRemoveAnAddress()) {
                throw ValidationException::withMessages([
                    'address' => ['Você precisa manter pelo menos um endereço cadastrado.'],
                ]);
            }

            try {
                // deleteOrFail() (not delete()) so PHPStan can see the catch below is reachable:
                // Model::delete()'s docblock only declares @throws \LogicException, so PHPStan
                // treats a catch(QueryException) around it as dead code. deleteOrFail() declares
                // @throws \Throwable, which covers QueryException. Its own transaction is only a
                // savepoint here; rethrowing below rolls back the outer transaction as a whole.
                $address->deleteOrFail();
            } catch (QueryException $exception) {
                if (! str_starts_with((string) $exception->getCode(), '23')) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'address' => ['Este endereço está vinculado a um ou mais agendamentos e não pode ser removido.'],
                ]);
            }
        });
    }
}
