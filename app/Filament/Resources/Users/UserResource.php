<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form ( Schema $schema ) : Schema
    {
        return UserForm ::configure ( $schema );
    }

    public static function table ( Table $table ) : Table
    {
        return UsersTable ::configure ( $table );
    }

    public static function getRelations () : array
    {
        return [
            //
        ];
    }

    public static function getNavigationItems () : array
    {
        $user = auth () -> user ();
        if ( !$user || !$user -> hasRole ( 'super_admin' ) ) {
            return [];
        }
        return parent ::getNavigationItems ();
    }

    public static function canViewAny () : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canCreate () : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canEdit ( $record ) : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canDelete ( $record ) : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function getPages () : array
    {
        return [
            'index'  => ListUsers ::route ( '/' ),
            'create' => CreateUser ::route ( '/create' ),
            'edit'   => EditUser ::route ( '/{record}/edit' ),
        ];
    }
}
