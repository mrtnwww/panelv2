<?php

namespace App\Http\Controllers\Productos;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\UsuarioTipoUsuario;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function listProducts(Request $request) {
        $user = $request->user();

        $usuarioId = $user?->id;
        $empresaId = $user?->empresa_id;

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', 10);

        // Verificar si es admin
        $isAdmin = UsuarioTipoUsuario::where('id_usuario', $usuarioId)
            ->join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
            ->where('subtipousuario.id', 2)
            ->exists();

        // Obtener productos directamente sin traer usuarios completos
        $productos = Producto::whereIn('user_id', function ($q) use ($empresaId) {
                $q->select('id')
                ->from('usuario')
                ->where('empresa_id', $empresaId);
            })
            ->applySearch($search)
            ->paginate($perPage);

        $productos->getCollection()->transform(function ($item) use ($isAdmin) {
            $item->isUsed = false;
            $item->isAdmin = $isAdmin;
            return $item;
        });

        return response()->json([
            'productos' => $productos
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $usuarioId = $user?->id;

        $productos = $request->input('productos', []);

        if (isset($productos['referencia'])) $productos = [$productos];

        foreach($productos as $item) {
            $producto = new Producto();
            $producto->referencia   = $item['referencia'];
            $producto->nombre       = $item['nombre'];
            $producto->precio       = $item['precio'];
            $producto->user_id      = $usuarioId;
            $producto->save();
        }

        return response()->json([
            'message' => 'Productos creados'
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'productos.referencia' => 'required|string',
            'productos.precio'     => 'required|numeric',
            'productos.nombre'     => 'required|string',
            'productos.id'         => 'required|numeric'
        ]);

        $productoData = $data['productos'];
        $producto = Producto::findOrFail($productoData['id']);

        $producto->referencia = $productoData['referencia'];
        $producto->nombre     = $productoData['nombre'];
        $producto->precio     = $productoData['precio'];

        $producto->save();

        return response()->json([
            'message' => 'Producto actualizado correctamente'
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|numeric'
        ]);

        Producto::findOrFail($data['id'])->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
}
