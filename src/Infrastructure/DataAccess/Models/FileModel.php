<?php

declare(strict_types=1);

namespace NetCode\Media\Infrastructure\DataAccess\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use NetCode\Domain\Laravel\IdentifierCast;
use NetCode\Media\Domain\Enums\FileStatus;
use NetCode\Media\Domain\ValueObjects\FileId;

/**
 * @property FileId $id
 * @property string $disk
 * @property string $key
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property string|null $checksum
 * @property FileStatus $status
 * @property string|null $uploaded_by
 * @property DateTimeImmutable|null $completed_at
 * @property DateTimeImmutable $created_at
 */
final class FileModel extends Model
{
    protected $table = 'files';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'id' => IdentifierCast::class.':'.FileId::class,
        'status' => FileStatus::class,
        'size' => 'integer',
        'completed_at' => 'immutable_datetime',
    ];
}
