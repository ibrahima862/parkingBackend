<?php

namespace App\Http\Controllers;

class TestController extends Controller{
   public function index(){

   try {
      $personne=[
         [
            'id'=>2,
            'nom'=>'ndoye',
            'prenom'=>'ibrahima',
            'age'=>12
         ],
         [
            'id'=>3,
            'nom'=>'fall',
            'prenom'=>'matar',
            'age'=>12
         ]
      ];
      return response()->json($personne, 200);
   } catch (\Exception $e) {
      return response()->json(['message' => 'Erreur'], 500);
   }
   }
}