<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared base for models that select locale-aware columns.
 *
 * For a bilingual column pair (`field` / `field_ar`), the frontend always
 * wants one value per row, already resolved for the current locale — not
 * both columns fetched and picked apart in PHP. localizedColumn() builds
 * that as a single SELECT expression: English is selected as-is; Arabic
 * selects the `_ar` column, falling back to the English column when the
 * Arabic value is empty, per the project's bilingual fallback rule.
 */
class Localized_model extends SqlModel
{
    /**
     * A SELECT expression for one bilingual column, aliased back to its
     * base name so the result row exposes only `field`, never `field_ar`.
     *
     * $column may be qualified with a table alias ('t.field'); the `_ar`
     * suffix is applied to the column part only ('t.field_ar').
     */
    protected function localizedColumn($column, $locale, $alias = null)
    {
        $alias = $alias !== null ? $alias : $column;
        $alias = strpos($alias, '.') !== false ? substr($alias, strrpos($alias, '.') + 1) : $alias;

        $qualified = $this->quoteQualifiedColumn($column);
        $qualifiedAr = $this->quoteQualifiedColumn($column . '_ar');

        if ($locale === 'ar') {
            return "IF(TRIM(COALESCE({$qualifiedAr}, '')) <> '', {$qualifiedAr}, {$qualified}) AS `{$alias}`";
        }

        return "{$qualified} AS `{$alias}`";
    }

    /** Backtick-quote a possibly table-qualified column ('t.field' -> `t`.`field`). */
    private function quoteQualifiedColumn($column)
    {
        $parts = explode('.', $column);

        return '`' . implode('`.`', $parts) . '`';
    }

    /** The current locale, normalized to 'ar' or 'en'. */
    protected function normalizeLocale($locale)
    {
        return $locale === 'ar' ? 'ar' : 'en';
    }
}
