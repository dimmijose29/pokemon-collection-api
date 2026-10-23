<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * TCGdex non raggiungibile o in errore dopo i tentativi: il job che la riceve
 * fallisce e la coda lo riprova, invece di proseguire con dati mancanti.
 */
class TcgdexException extends RuntimeException
{
}
