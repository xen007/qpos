<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Declaration de routes dont l'acces est verifie par une permission Spatie.
 *
 * Objectif : porter le controle d'acces au niveau de la route (middleware
 * permission:xxx) plutot que dans le corps des controleurs (abort_if), afin
 * que chaque action soit protegee avant meme d'entrer dans le controleur.
 *
 * Les controleurs conservent leurs verifications uniquement lorsque la
 * permission depend de la requete (voir PurchaseController, OrderController).
 */
class PermissionRoutes
{
    /**
     * Ressource REST (index, create, store, show, edit, update, destroy)
     * protegee action par action.
     *
     * @param  string|array<string, string|null>  $permissions
     *   - chaine : prefixe commun ; chaque action recoit le suffixe attendu
     *     (index/show -> _view, create/store -> _create, edit/update -> _update,
     *     destroy -> _delete) ;
     *   - tableau : permission par action ; une valeur null laisse l'action
     *     sans middleware, ce qui reproduit un controleur sans verification.
     */
    public static function resource(string $name, string $controller, string|array $permissions): void
    {
        $map = is_array($permissions) ? $permissions : [
            'index' => $permissions.'_view',
            'create' => $permissions.'_create',
            'store' => $permissions.'_create',
            'show' => $permissions.'_view',
            'edit' => $permissions.'_update',
            'update' => $permissions.'_update',
            'destroy' => $permissions.'_delete',
        ];

        $parameter = '{'.Str::singular($name).'}';

        self::register('get', $name, $controller, 'index', $map['index'] ?? null, $name.'.index');
        self::register('get', $name.'/create', $controller, 'create', $map['create'] ?? null, $name.'.create');
        self::register('post', $name, $controller, 'store', $map['store'] ?? null, $name.'.store');
        self::register('get', $name.'/'.$parameter, $controller, 'show', $map['show'] ?? null, $name.'.show');
        self::register('get', $name.'/'.$parameter.'/edit', $controller, 'edit', $map['edit'] ?? null, $name.'.edit');
        self::register(['put', 'patch'], $name.'/'.$parameter, $controller, 'update', $map['update'] ?? null, $name.'.update');
        self::register('delete', $name.'/'.$parameter, $controller, 'destroy', $map['destroy'] ?? null, $name.'.destroy');
    }

    /**
     * Enregistre une action et applique le middleware de permission si besoin.
     *
     * @param  string|array<int, string>  $methods
     */
    protected static function register(
        string|array $methods,
        string $uri,
        string $controller,
        string $action,
        ?string $permission,
        string $name
    ): void {
        $route = is_array($methods)
            ? Route::match($methods, $uri, [$controller, $action])
            : Route::$methods($uri, [$controller, $action]);

        $route->name($name);

        if ($permission !== null) {
            $route->middleware('permission:'.$permission);
        }
    }
}
