<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Parametrage\MarqueController;
use App\Http\Controllers\Parametrage\CommuneController;
use App\Http\Controllers\Parametrage\CouponTicketController;
use App\Http\Controllers\Parametrage\StockTicketController;
use App\Http\Controllers\Parametrage\CompagniePetrolierController;
use App\Http\Controllers\Parametrage\MagazinController;
use App\Http\Controllers\Parametrage\ModeleController;
use App\Http\Controllers\Parametrage\TypeInterventionController;
use App\Http\Controllers\Parametrage\CategorieArticleController;
use App\Http\Controllers\Parametrage\FournisseurController;
use App\Http\Controllers\Parametrage\TypeAffectationController;
use App\Http\Controllers\Parametrage\TypeMouvementController;
use App\Http\Controllers\Parametrage\BureauController;
use App\Http\Controllers\Parametrage\StatusImmoController;
use App\Http\Controllers\Parametrage\TypeImmoController;
use App\Http\Controllers\Parametrage\SousTypeImmoController;
use App\Http\Controllers\Parametrage\GroupeTypeImmoController;
use App\Http\Controllers\Parametrage\ModuleController;
use App\Http\Controllers\Parametrage\FonctionnaliteController;
use App\Http\Controllers\Parametrage\PermissionController;
use App\Http\Controllers\Parametrage\RoleController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DashboardStockController;
use App\Http\Controllers\MouvementStockController;
use App\Http\Controllers\Parametrage\EmployeController;
use App\Http\Controllers\Auth\AuthentificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\ImmobilisationController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\InterventionVehiculeController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\MouvementTicketController;
use App\Http\Controllers\RetourTicketController;
use App\Http\Controllers\AnnulationTicketController;
use App\Http\Controllers\TrajetController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/stock/coupon-compagnies', [CouponTicketController::class, 'getCouponTicketsWithCompagnies']);
Route::apiResource('marques', MarqueController::class);
Route::apiResource('communes', CommuneController::class);
Route::apiResource('coupon_tickets', CouponTicketController::class);
Route::apiResource('stock_coupon_tickets', StockTicketController::class);
Route::apiResource('compagnie_petrolier', CompagniePetrolierController::class);
Route::get('compagnie_petrolier-imprimer', [CompagniePetrolierController::class, 'imprimer']);
Route::apiResource('magazins', MagazinController::class);
Route::apiResource('modeles', ModeleController::class);
Route::apiResource('type-interventions', TypeInterventionController::class);
Route::apiResource('categorie-articles', CategorieArticleController::class);
Route::apiResource('fournisseurs', FournisseurController::class);
Route::get('fournisseurs-imprimer', [FournisseurController::class, 'imprimer']);
Route::apiResource('employes', EmployeController::class);
Route::get('employes-imprimer', [EmployeController::class, 'imprimer']);
Route::apiResource('type_affectations', TypeAffectationController::class);
Route::apiResource('type_mouvements', TypeMouvementController::class);
Route::apiResource('bureaux', BureauController::class);
Route::apiResource('status_immos', StatusImmoController::class);
Route::apiResource('type_immos', TypeImmoController::class);
Route::apiResource('sous_type_immos', SousTypeImmoController::class);
Route::apiResource('groupe_type_immos', GroupeTypeImmoController::class);
Route::apiResource('modules', ModuleController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('fonctionnalites', FonctionnaliteController::class);
Route::apiResource('permissions', PermissionController::class);
Route::apiResource('articles', ArticleController::class);
Route::get('etat_stock-imprimer', [ArticleController::class, 'imprimer']);
Route::apiResource('immobilisations', ImmobilisationController::class);
Route::apiResource('interventions', InterventionController::class);
Route::apiResource('transferts', TransfertController::class);
Route::apiResource('retour-ticket', RetourTicketController::class);
Route::apiResource('annulation-ticket', AnnulationTicketController::class);
Route::apiResource('trajets', TrajetController::class);
Route::get('ancien-info/{id}', [TransfertController::class, 'getOldInfo']);

Route::get('mouvement-info/{id}', [RetourTicketController::class, 'getMouvementInfo']);
Route::get('mouvement-ticket/getAllSortieTicketWhereNotInRetour', [RetourTicketController::class, 'getAllSortieTicketWhereNotInRetour']);


Route::apiResource('vehicules', VehiculeController::class);
Route::post('vehicules/batch', [VehiculeController::class, 'storeBatch']);

// Nouvelle route pour l'ajout par lot
Route::get('dashboard/stock', [DashboardStockController::class, 'indexArticles']);
Route::get('dashboard/dashInfoStock', [DashboardStockController::class, 'dashInfoStock']);
Route::post('articles/batch', [ArticleController::class, 'storeBatch']);

Route::get('mouvement-stock/entree', [MouvementStockController::class, 'indexEntreeStock']);
Route::post('mouvement-stock/entree', [MouvementStockController::class, 'storeEntreeStock']);
Route::post('/mouvement-stock/entree-multiple', [MouvementStockController::class, 'storeMultipleEntreeStock']);
Route::put('/mouvement-stock/entree/{id}', [MouvementStockController::class, 'updateEntreeStock']);
Route::delete('mouvement-stock/entree/{id}', [MouvementStockController::class, 'deleteEntreeStock']);

Route::get('mouvement-stock/sortie', [MouvementStockController::class, 'indexSortieStock']);
Route::post('mouvement-stock/sortie', [MouvementStockController::class, 'storeSortieStock']);
Route::delete('mouvement-stock/sortie/{id}', [MouvementStockController::class, 'deleteSortieStock']);
Route::get('quantite-disponible/{id}', [MouvementStockController::class, 'getQuantiteDisponible']);
Route::put('mouvement-stock/sortie/{id}', [MouvementStockController::class, 'updateSortieStock']);
Route::patch('mouvement-stock/sortie/{id}', [MouvementStockController::class, 'updateDemandeStock']);

Route::get('mouvement-ticket/entree', [MouvementTicketController::class, 'indexEntreeTicket']);
Route::post('mouvement-ticket/entree', [MouvementTicketController::class, 'storeEntreeTicket']);
Route::put('/mouvement-ticket/entree/{id}', [MouvementTicketController::class, 'updateEntreeTicket']);
Route::delete('mouvement-ticket/entree/{id}', [MouvementTicketController::class, 'deleteEntreeTicket']);

Route::get('mouvement-ticket/sortie', [MouvementTicketController::class, 'indexSortieTicket']);
Route::post('mouvement-ticket/sortie', [MouvementTicketController::class, 'storeSortieTicket']);
Route::get('quantite-disponible-ticket/{idCoupon}/{idCompagnie}', [MouvementTicketController::class, 'getQuantiteDisponible']);
Route::put('/mouvement-ticket/sortie/{id}', [MouvementTicketController::class, 'updateSortieTicket']);
Route::delete('mouvement-ticket/sortie/{id}', [MouvementTicketController::class, 'deleteSortieTicket']);
Route::post('get-quantite-ticket-attribution', [MouvementTicketController::class, 'getQuantiteTicketAttribution']);



// Route::post('reset-password/{user}', [AuthentificationController::class, 'resetPassword']);
Route::post('register', [AuthentificationController::class, 'register']);
Route::post('login', [AuthentificationController::class, 'login'])->name("login");


Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOTP']);
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOTP']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::post('/bonjour', [ForgotPasswordController::class, 'direBonjour']);

Route::apiResource('intervention-vehicules', InterventionVehiculeController::class);

// Route::get('/intervention-vehicules', [InterventionVehiculeController::class, 'index']);
// Route::post('/intervention-vehicules', [InterventionVehiculeController::class, 'store']);
// Route::get('/intervention-vehicules/{interventionVehicule}', [InterventionVehiculeController::class, 'show']);
// Route::put('/intervention-vehicules/{interventionVehicule}', [InterventionVehiculeController::class, 'update']);
// Route::delete('/intervention-vehicules/{interventionVehicule}', [InterventionVehiculeController::class, 'destroy']);
