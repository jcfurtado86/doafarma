<?php

declare(strict_types = 1);

namespace App\Actions\Address;

use App\Models\Address;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteAddressAction
{
    /**
     * Execute the action.
     *
     * @throws ValidationException if the address is referenced by an existing record
     */
    public function execute(Address $address): void
    {
        try {
            // deleteOrFail() (not delete()) so PHPStan can see the catch below is reachable:
            // Model::delete()'s docblock only declares @throws \LogicException, so PHPStan
            // treats a catch(QueryException) around it as dead code. deleteOrFail() declares
            // @throws \Throwable, which covers QueryException. Behavior is otherwise identical
            // for an existing model (it just also wraps the delete in a DB transaction).
            $address->deleteOrFail();
        } catch (QueryException $exception) {
            if (! str_starts_with((string) $exception->getCode(), '23')) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'address' => ['Este endereço está vinculado a um ou mais agendamentos e não pode ser removido.'],
            ]);
        }
    }
}
