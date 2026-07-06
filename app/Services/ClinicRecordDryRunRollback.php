<?php

namespace App\Services;

/**
 * Thrown at the end of a group's processing when running in --dry-run mode,
 * so DB::transaction() rolls back just that one group instead of needing a
 * single giant transaction wrapped around the entire run.
 */
class ClinicRecordDryRunRollback extends \RuntimeException
{
}
