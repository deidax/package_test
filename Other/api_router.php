Route::prefix('user')->group(function (){
    Route::middleware('auth:sanctum')->get('/', function (Request $request) {
        return $request->user();
    });

    Route::get('/', function (Request $request) {
        return $request->user()->buildUserApiWithExtraData();
    });
    Route::post("/login", [LoginController::class, "login"])->name('user.login');
    Route::post("/logout", [LoginController::class, "logout"])->name('user.logout');
    Route::get("/all", [UserController::class, "index"])->name('user.index')->middleware('checkIfAnyPermissions:lire_utilisateur|modifier_utilisateur');
    Route::get("/all_by_types", [UserController::class, "getAllUsersByTypeAndExtraFiledsData"])->name('user.by.type');
    Route::post("/", [UserController::class, "store"])->name('user.store')->middleware('checkPermissions:ajouter_utilisateur');
    Route::get('/block/{user}', [UserController::class, "blockUser"])->name('user.block');
    Route::delete("/destroy/{user}", [UserController::class, "destroy"])->name('user.destroy')->middleware('checkPermissions:supprimer_utilisateur');
    Route::post("/change/password", [UserController::class, "changePassword"])->name('user.change.password');
    Route::post("/update", [UserController::class, "update"])->name('user.update');
    Route::post("/switch_user", [UserController::class, "switchUser"])->name('user.switch');
    Route::post("/restore_user", [UserController::class, "restoreUserTolatest"])->name('user.restore.to.latest');
    // users management
    Route::prefix('management')->group(function(){
        Route::get('/user_model_name', [UserController::class, "call_user_model_name"])->name('user.model.name');
        Route::get('/grades', [UserController::class, "call_user_ranks"])->name('user.grades');
        Route::post('/get_user_extra_fields_schema', [UserController::class, "call_extra_fields_form_schema"])->name('user.extra_fields_form_schema');
    });

    // permission
    Route::prefix('permission')->group(function(){
        Route::get('/categorie/{user}', [CategoryPermissionController::class, "index"])->name('categories.permissions');
        Route::post('/set', [UserController::class, "setUserPermissions"])->name('user.setPermissions')->middleware('checkPermissions:modifier_les_permissions');
        // Route::get('/grades', [UserController::class, "call_user_ranks"])->name('user.grades');
        // Route::post('/get_user_extra_fields_schema', [UserController::class, "call_extra_fields_form_schema"])->name('user.extra_fields_form_schema');
    });

    Route::get("abilities", [PermissionController::class, "index"]);
    Route::get("check_auth", [LoginController::class, "checkAuth"]);

    

});