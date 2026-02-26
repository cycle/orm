<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

/**
 * Load options for BelongsToMorphed relations.
 *
 * This relation uses a separate loading mechanism (not JoinableLoader),
 * so it only supports a limited set of options.
 */
final class BelongsToMorphedLoadOptions extends LoadOptions {}
