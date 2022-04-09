<?php

namespace App\Models\Admin;

use Illuminate\Support\Facades\DB;

class Menu extends \Encore\Admin\Auth\Database\Menu
{
    /**
     * @return array
     */
    public function allNodes(): array
    {
        $connection = config('admin.database.connection') ?: config('database.default');
        $orderColumn = DB::connection($connection)->getQueryGrammar()->wrap($this->orderColumn);

        $byOrder = 'ROOT ASC,'.$orderColumn;

        $query = static::query();

        if (config('admin.check_menu_roles') !== false) {
            $query->with('roles');
        }
        return $query->selectRaw('id, parent_id, title, icon, uri, permission,'.$orderColumn.' ROOT')->orderByRaw($byOrder)->get()->toArray();
    }
}
