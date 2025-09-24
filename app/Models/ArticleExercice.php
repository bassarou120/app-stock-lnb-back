<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleExercice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_exercice';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_article',
        'id_exercice',
        'stock_debut_exercice',
        'stock_fin_exercice',
        'cmp_debut_exercice',
        'cmp_fin_exercice',
    ];

    /**
     * Indicate if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    // You can define relationships if needed
    public function article(): BelongsTo
    {
        // 'id_article' is the foreign key in the pivot table pointing to the 'articles' table
        return $this->belongsTo(Article::class, 'id_article');
    }

    // Define the relationship to the Exercice model
    public function exercice(): BelongsTo
    {
        // 'id_exercice' is the foreign key in the pivot table pointing to the 'exercices' table
        return $this->belongsTo(Exercice::class, 'id_exercice');
    }
}