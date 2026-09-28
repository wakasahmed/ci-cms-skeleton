<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared tree-building and payload-validation logic for the Main Menu and
 * Footer Menu manager screens. Both screens store their arrangement on the
 * same `pages` table (different column families) as a shallow hierarchy:
 * an 'active' tree capped at $maxDepth levels, and a flat 'available' list.
 *
 * Operates on plain arrays only (no DB access), so callers must first fetch
 * rows with generic keys: page_id, page_parent_id, page_slug, page_name,
 * menu_name, menu_parent_id, menu_order and menu_active (the model aliases its type-specific columns to
 * these generic names in SQL).
 */
class Page_menu_hierarchy
{
    /**
     * Builds array('active' => <tree>, 'available' => <flat list>) from a
     * flat row set. Cycles, self-parenting, missing parents, and depth
     * overflow are recovered as top-level active items rather than dropped.
     */
    public function buildTree(array $rows, $maxDepth)
    {
        $maxDepth = max(1, (int) $maxDepth);
        $activeById = array();

        foreach ($rows as $row) {
            if ((int) $row['menu_active'] === 1) {
                $activeById[(int) $row['page_id']] = $row;
            }
        }

        $roots = array();

        foreach ($activeById as $id => $row) {
            if ($this->resolveDepth($activeById, $id, $maxDepth) === 0) {
                $roots[$id] = $this->toNode($row, array());
            }
        }

        if ($maxDepth > 1) {
            foreach ($activeById as $id => $row) {
                if ($this->resolveDepth($activeById, $id, $maxDepth) !== 1) {
                    continue;
                }

                $parentId = (int) $row['menu_parent_id'];

                if (isset($roots[$parentId])) {
                    $roots[$parentId]['children'][] = $this->toNode($row, array());
                }
            }

            foreach ($roots as &$root) {
                usort($root['children'], function ($a, $b) {
                    return $a['menu_order'] <=> $b['menu_order'];
                });
            }
            unset($root);
        }

        uasort($roots, function ($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });

        $available = array();

        foreach ($rows as $row) {
            if ((int) $row['menu_active'] === 0) {
                $available[] = $this->toNode($row, array());
            }
        }

        usort($available, function ($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });

        return array('active' => array_values($roots), 'available' => $available);
    }

    /**
     * Validates a decoded array('active' => [...], 'available' => [...])
     * payload against the allowed page set and flattens it into a flat list
     * of updates: array('page_id', 'menu_parent_id', 'menu_order', 'menu_active').
     * Returns NULL on any validation failure.
     */
    public function flattenPayload($payload, array $allowedPages, $maxDepth)
    {
        if (!is_array($payload) || !isset($payload['active']) || !isset($payload['available']) || !is_array($payload['active']) || !is_array($payload['available'])) {
            return NULL;
        }

        $maxDepth = max(1, (int) $maxDepth);
        $rows = array();
        $seenIds = array();

        if (!$this->flattenActiveNodes($payload['active'], 0, 0, $maxDepth, $allowedPages, $seenIds, $rows)) {
            return NULL;
        }

        if (!$this->flattenAvailableNodes($payload['available'], $allowedPages, $seenIds, $rows)) {
            return NULL;
        }

        if (count($seenIds) !== count($allowedPages)) {
            return NULL;
        }

        return $rows;
    }

    /**
     * Walks a row's parent chain and returns its recoverable depth:
     * 0 = top-level (parent id 0, or an unresolved/cyclic/orphaned/inactive
     *     parent chain, which is treated as top-level rather than dropped),
     * 1 = valid child of an active top-level row,
     * 2+ = would exceed $maxDepth, flattened to top-level.
     */
    private function resolveDepth($byId, $id, $maxDepth)
    {
        $row = $byId[$id];
        $parentId = (int) $row['menu_parent_id'];

        if ($parentId === 0 || $parentId === $id) {
            return 0;
        }

        if ($maxDepth <= 1 || !isset($byId[$parentId])) {
            return 0;
        }

        $parentDepth = 0;
        $visited = array($id => TRUE);
        $currentId = $parentId;

        while (TRUE) {
            if (isset($visited[$currentId])) {
                return 0;
            }
            $visited[$currentId] = TRUE;

            if (!isset($byId[$currentId])) {
                return 0;
            }

            $currentParentId = (int) $byId[$currentId]['menu_parent_id'];

            if ($currentParentId === 0 || $currentParentId === $currentId) {
                return $parentDepth === 0 ? 1 : 0;
            }

            $parentDepth++;

            if ($parentDepth >= $maxDepth || !isset($byId[$currentParentId])) {
                return 0;
            }

            $currentId = $currentParentId;
        }
    }

    private function toNode($row, $children)
    {
        return array(
            'page_id' => (int) $row['page_id'],
            'page_parent_id' => (int) $row['page_parent_id'],
            'page_slug' => isset($row['page_slug']) ? (string) $row['page_slug'] : '',
            'page_name' => (string) $row['page_name'],
            'menu_name' => (string) $row['menu_name'],
            'menu_order' => (int) $row['menu_order'],
            'children' => $children,
        );
    }

    private function flattenActiveNodes($nodes, $depth, $parentId, $maxDepth, $allowedPages, &$seenIds, &$rows)
    {
        if ($depth >= $maxDepth) {
            return FALSE;
        }

        $order = 1;

        foreach ($nodes as $node) {
            if (!is_array($node) || !isset($node['page_id'])) {
                return FALSE;
            }

            $pageId = filter_var($node['page_id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));

            if ($pageId === FALSE || !isset($allowedPages[$pageId]) || isset($seenIds[$pageId]) || $pageId === $parentId) {
                return FALSE;
            }

            $seenIds[$pageId] = TRUE;
            $rows[] = array(
                'page_id' => $pageId,
                'menu_parent_id' => $parentId,
                'menu_order' => $order,
                'menu_active' => 1,
            );

            $children = isset($node['children']) && is_array($node['children']) ? $node['children'] : array();

            if (!empty($children) && !$this->flattenActiveNodes($children, $depth + 1, $pageId, $maxDepth, $allowedPages, $seenIds, $rows)) {
                return FALSE;
            }

            $order++;
        }

        return TRUE;
    }

    private function flattenAvailableNodes($nodes, $allowedPages, &$seenIds, &$rows)
    {
        $order = 1;

        foreach ($nodes as $node) {
            if (!is_array($node) || !isset($node['page_id'])) {
                return FALSE;
            }

            if (!empty($node['children'])) {
                return FALSE;
            }

            $pageId = filter_var($node['page_id'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));

            if ($pageId === FALSE || !isset($allowedPages[$pageId]) || isset($seenIds[$pageId])) {
                return FALSE;
            }

            $seenIds[$pageId] = TRUE;
            $rows[] = array(
                'page_id' => $pageId,
                'menu_parent_id' => 0,
                'menu_order' => $order,
                'menu_active' => 0,
            );
            $order++;
        }

        return TRUE;
    }
}
