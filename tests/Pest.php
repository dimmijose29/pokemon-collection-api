<?php

use Tests\TestCase;

/*
| Tutti i test girano sul TestCase dell'app, che rifiuta qualsiasi database
| diverso da `testing` prima che RefreshDatabase possa toccarlo.
*/
uses(TestCase::class)->in('Feature', 'Unit');
