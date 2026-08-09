<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de prueba para el proyecto ECCOMERCE POC: tienda de productos
 * INTEGRALES / NATURALES. Genera 100+ productos con categorías coherentes
 * e imágenes (placeholder por categoría).
 *
 * Uso:  php artisan db:seed --class=EcommercePocSeeder --force
 * Toma el proyecto por slug 'eccomerce-poc-mj6a' (o el que empiece con 'eccomerce').
 *
 * Es idempotente por nombre: no duplica productos ya creados.
 */
class EcommercePocSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('slug', 'eccomerce-poc-mj6a')->first()
            ?? Project::where('slug', 'like', 'eccomerce%')->first()
            ?? Project::where('name', 'like', '%ECCOMERCE%')->first();

        if (!$project) {
            $this->command->error('No encontré el proyecto ECCOMERCE POC.');
            return;
        }
        $this->command->info("Proyecto: {$project->name} (id {$project->id})");

        // ── Catálogo: categoría => [color hex, [productos...]] ──
        // Cada producto: [nombre, precio, unidad]
        $catalogo = [
            'Granos y Cereales Integrales' => ['16a34a', [
                ['Quinua blanca orgánica', 12.90, 'kg'], ['Quinua roja andina', 14.50, 'kg'],
                ['Quinua negra', 15.90, 'kg'], ['Kiwicha (amaranto)', 11.90, 'kg'],
                ['Cañihua molida', 13.90, 'kg'], ['Avena en hojuelas integral', 8.50, 'kg'],
                ['Avena precocida', 7.90, 'kg'], ['Arroz integral', 6.90, 'kg'],
                ['Trigo mote', 5.50, 'kg'], ['Cebada perlada', 5.90, 'kg'],
                ['Maíz morado', 7.50, 'kg'], ['Granola artesanal', 16.90, 'bolsa 500g'],
            ]],
            'Frutos Secos y Semillas' => ['ca8a04', [
                ['Almendras naturales', 32.90, 'kg'], ['Nueces peladas', 38.90, 'kg'],
                ['Pecanas', 42.90, 'kg'], ['Maní tostado sin sal', 12.90, 'kg'],
                ['Pistachos', 49.90, 'kg'], ['Castañas amazónicas', 45.90, 'kg'],
                ['Semillas de chía', 18.90, 'bolsa 250g'], ['Semillas de linaza', 9.90, 'bolsa 250g'],
                ['Semillas de girasol', 11.90, 'bolsa 250g'], ['Semillas de zapallo', 16.90, 'bolsa 250g'],
                ['Ajonjolí (sésamo)', 10.90, 'bolsa 250g'], ['Mix de frutos secos', 24.90, 'bolsa 300g'],
            ]],
            'Endulzantes Naturales' => ['b45309', [
                ['Miel de abeja pura', 22.90, 'frasco 500g'], ['Miel de eucalipto', 25.90, 'frasco 500g'],
                ['Panela granulada', 8.90, 'bolsa 500g'], ['Azúcar de coco', 15.90, 'bolsa 500g'],
                ['Stevia en polvo', 12.90, 'caja 100g'], ['Stevia líquida', 14.90, 'frasco 30ml'],
                ['Sirope de agave', 19.90, 'frasco 350ml'], ['Melaza de caña', 11.90, 'frasco 500g'],
                ['Chancaca en bloque', 6.90, 'unidad'], ['Miel de maple', 34.90, 'frasco 250ml'],
            ]],
            'Superalimentos' => ['7c3aed', [
                ['Maca en polvo', 18.90, 'bolsa 250g'], ['Cacao en polvo puro', 16.90, 'bolsa 250g'],
                ['Cacao nibs', 19.90, 'bolsa 200g'], ['Camu camu en polvo', 22.90, 'bolsa 150g'],
                ['Lúcuma en polvo', 14.90, 'bolsa 250g'], ['Aguaje en polvo', 17.90, 'bolsa 200g'],
                ['Spirulina en polvo', 28.90, 'bolsa 150g'], ['Moringa en polvo', 21.90, 'bolsa 200g'],
                ['Cúrcuma orgánica', 13.90, 'bolsa 200g'], ['Jengibre en polvo', 12.90, 'bolsa 200g'],
                ['Algarrobina', 15.90, 'frasco 350ml'], ['Colágeno marino', 45.90, 'bolsa 300g'],
            ]],
            'Snacks Saludables' => ['dc2626', [
                ['Chips de plátano', 6.90, 'bolsa 150g'], ['Chips de camote', 7.50, 'bolsa 150g'],
                ['Habas tostadas', 5.90, 'bolsa 200g'], ['Garbanzos tostados', 6.50, 'bolsa 200g'],
                ['Barritas de cereal', 3.50, 'unidad'], ['Barritas energéticas', 4.90, 'unidad'],
                ['Frutas deshidratadas mix', 14.90, 'bolsa 200g'], ['Mango deshidratado', 16.90, 'bolsa 150g'],
                ['Piña deshidratada', 15.90, 'bolsa 150g'], ['Pasas rubias', 9.90, 'bolsa 250g'],
                ['Arándanos deshidratados', 22.90, 'bolsa 150g'], ['Coco rallado', 8.90, 'bolsa 200g'],
            ]],
            'Infusiones y Té' => ['0891b2', [
                ['Té verde', 9.90, 'caja 25u'], ['Té de manzanilla', 7.90, 'caja 25u'],
                ['Té de menta', 8.50, 'caja 25u'], ['Té de anís', 7.50, 'caja 25u'],
                ['Infusión de hierba luisa', 8.90, 'caja 25u'], ['Té de boldo', 9.50, 'caja 25u'],
                ['Té de uña de gato', 12.90, 'caja 25u'], ['Té de jengibre y limón', 10.90, 'caja 25u'],
                ['Té de cúrcuma', 11.90, 'caja 25u'], ['Mate de coca', 8.90, 'caja 25u'],
                ['Té chai especiado', 13.90, 'caja 20u'], ['Infusión relajante nocturna', 12.90, 'caja 20u'],
            ]],
            'Aceites y Vinagres' => ['65a30d', [
                ['Aceite de oliva extra virgen', 28.90, 'botella 500ml'], ['Aceite de coco', 24.90, 'frasco 400ml'],
                ['Aceite de sacha inchi', 32.90, 'botella 250ml'], ['Aceite de ajonjolí', 19.90, 'botella 250ml'],
                ['Aceite de linaza', 22.90, 'botella 250ml'], ['Vinagre de manzana orgánico', 14.90, 'botella 500ml'],
                ['Vinagre balsámico', 18.90, 'botella 250ml'], ['Ghee (mantequilla clarificada)', 26.90, 'frasco 250g'],
            ]],
            'Harinas Integrales' => ['9333ea', [
                ['Harina integral de trigo', 6.90, 'kg'], ['Harina de quinua', 12.90, 'bolsa 500g'],
                ['Harina de almendras', 29.90, 'bolsa 500g'], ['Harina de coco', 18.90, 'bolsa 500g'],
                ['Harina de plátano', 9.90, 'bolsa 500g'], ['Harina de maca', 16.90, 'bolsa 500g'],
                ['Harina de algarroba', 14.90, 'bolsa 500g'], ['Harina de garbanzo', 8.90, 'bolsa 500g'],
                ['Harina de avena', 7.90, 'bolsa 500g'], ['Harina de kiwicha', 13.90, 'bolsa 500g'],
            ]],
            'Legumbres y Menestras' => ['ea580c', [
                ['Lentejas', 6.90, 'kg'], ['Garbanzos', 8.90, 'kg'], ['Frijol canario', 9.90, 'kg'],
                ['Frijol negro', 8.50, 'kg'], ['Pallares', 11.90, 'kg'], ['Arvejas partidas', 7.50, 'kg'],
                ['Habas secas', 7.90, 'kg'], ['Soya en grano', 6.50, 'kg'],
            ]],
            'Suplementos Naturales' => ['0d9488', [
                ['Proteína de arveja', 52.90, 'bolsa 500g'], ['Proteína vegana mix', 58.90, 'bolsa 500g'],
                ['Omega 3 vegetal', 39.90, 'frasco 60u'], ['Multivitamínico natural', 34.90, 'frasco 60u'],
                ['Probióticos', 42.90, 'frasco 30u'], ['Fibra de psyllium', 24.90, 'bolsa 300g'],
                ['Levadura nutricional', 21.90, 'bolsa 200g'], ['Magnesio en polvo', 28.90, 'bolsa 250g'],
            ]],
        ];

        $totalCreados = 0; $skuBase = 1000;

        foreach ($catalogo as $catNombre => [$color, $productos]) {
            $categoria = Category::firstOrCreate(
                ['project_id' => $project->id, 'name' => $catNombre],
                ['type' => 'product', 'color' => '#' . $color, 'is_active' => true, 'sort_order' => 0]
            );

            foreach ($productos as [$nombre, $precio, $unidad]) {
                // Idempotente: no duplicar
                if (Product::where('project_id', $project->id)->where('name', $nombre)->exists()) {
                    continue;
                }

                $costo = round($precio * 0.62, 2); // margen ~38%
                $producto = Product::create([
                    'project_id'      => $project->id,
                    'category_id'     => $categoria->id,
                    'name'            => $nombre,
                    'sku'             => 'NAT-' . (++$skuBase),
                    'description'     => "{$nombre} — producto natural / integral. Presentación: {$unidad}.",
                    'price'           => $precio,
                    'price_suggested' => $precio,
                    'price_min'       => round($precio * 0.85, 2),
                    'price_max'       => round($precio * 1.30, 2),
                    'cost'            => $costo,
                    'unit'            => $unidad,
                    'stock'           => rand(15, 200),
                    'stock_min'       => 5,
                    'is_available'    => true,
                    'sort_order'      => 0,
                ]);

                // Imagen placeholder por categoría (color de la categoría + nombre)
                $texto = urlencode(Str::limit($nombre, 22, ''));
                $url = "https://placehold.co/600x600/{$color}/ffffff/png?text={$texto}";
                ProductImage::create([
                    'product_id' => $producto->id,
                    'url'        => $url,
                    'is_main'    => true,
                    'sort_order' => 0,
                ]);

                $totalCreados++;
            }
        }

        $this->command->info("Productos creados: {$totalCreados}");
        $this->command->info("Total en el proyecto: " . Product::where('project_id', $project->id)->count());
        $this->command->info("Categorías: " . Category::where('project_id', $project->id)->count());
    }
}
