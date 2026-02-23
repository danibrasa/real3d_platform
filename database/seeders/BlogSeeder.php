<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            ['name' => 'Guia de Inversion', 'name_en' => 'Investment Guide', 'slug' => 'guia-inversion', 'description' => 'Guias y consejos para inversores inmobiliarios', 'description_en' => 'Guides and tips for real estate investors', 'color' => '#06b6d4', 'sort_order' => 1],
            ['name' => 'Mercado Inmobiliario', 'name_en' => 'Real Estate Market', 'slug' => 'mercado-inmobiliario', 'description' => 'Analisis y tendencias del mercado', 'description_en' => 'Market analysis and trends', 'color' => '#8b5cf6', 'sort_order' => 2],
            ['name' => 'Vida en Republica Dominicana', 'name_en' => 'Life in Dominican Republic', 'slug' => 'vida-en-rd', 'description' => 'Todo sobre vivir en RD como extranjero', 'description_en' => 'Everything about living in DR as a foreigner', 'color' => '#10b981', 'sort_order' => 3],
            ['name' => 'Legal y Fiscal', 'name_en' => 'Legal & Tax', 'slug' => 'legal-fiscal', 'description' => 'Aspectos legales e impositivos de la inversion', 'description_en' => 'Legal and tax aspects of investment', 'color' => '#f59e0b', 'sort_order' => 4],
            ['name' => 'Proyectos Destacados', 'name_en' => 'Featured Projects', 'slug' => 'proyectos-destacados', 'description' => 'Analisis de los mejores proyectos disponibles', 'description_en' => 'Analysis of the best available projects', 'color' => '#3b82f6', 'sort_order' => 5],
            ['name' => 'Turismo y Rentabilidad', 'name_en' => 'Tourism & Returns', 'slug' => 'turismo-rentabilidad', 'description' => 'Cifras de turismo y rendimiento del alquiler', 'description_en' => 'Tourism figures and rental returns', 'color' => '#ef4444', 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            BlogCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Tags
        $tags = [
            ['name' => 'Punta Cana', 'name_en' => 'Punta Cana', 'slug' => 'punta-cana'],
            ['name' => 'Inversion', 'name_en' => 'Investment', 'slug' => 'inversion'],
            ['name' => 'Cap Cana', 'name_en' => 'Cap Cana', 'slug' => 'cap-cana'],
            ['name' => 'Extranjeros', 'name_en' => 'Foreigners', 'slug' => 'extranjeros'],
            ['name' => 'Impuestos', 'name_en' => 'Taxes', 'slug' => 'impuestos'],
            ['name' => 'Rentabilidad', 'name_en' => 'Profitability', 'slug' => 'rentabilidad'],
            ['name' => 'Airbnb', 'name_en' => 'Airbnb', 'slug' => 'airbnb'],
            ['name' => 'CONFOTUR', 'name_en' => 'CONFOTUR', 'slug' => 'confotur'],
        ];

        foreach ($tags as $tag) {
            BlogTag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }

        $author = User::where('role', 'superadmin')->first();
        $guiaInversion = BlogCategory::where('slug', 'guia-inversion')->first();
        $legalFiscal = BlogCategory::where('slug', 'legal-fiscal')->first();
        $turismoRentabilidad = BlogCategory::where('slug', 'turismo-rentabilidad')->first();

        // Post 1
        $post1 = BlogPost::firstOrCreate(['slug' => 'por-que-invertir-en-punta-cana-2026'], [
            'title' => 'Por que invertir en Punta Cana en 2026: 7 razones clave',
            'title_en' => 'Why invest in Punta Cana in 2026: 7 key reasons',
            'slug' => 'por-que-invertir-en-punta-cana-2026',
            'excerpt' => 'Descubre por que Punta Cana sigue siendo una de las mejores opciones de inversion inmobiliaria en el Caribe para 2026.',
            'excerpt_en' => 'Discover why Punta Cana remains one of the best real estate investment options in the Caribbean for 2026.',
            'body' => "## Por que Punta Cana es la joya del Caribe para invertir\n\nPunta Cana no es solo un destino turistico de clase mundial. Es uno de los mercados inmobiliarios mas dinamicos y rentables de America Latina. En 2026, las razones para invertir aqui son mas solidas que nunca.\n\n### 1. Turismo en cifras record\n\nRepublica Dominicana recibio mas de 10 millones de turistas en 2025, y Punta Cana concentra mas del 60% de ese flujo. Esto se traduce en una demanda constante de alojamiento vacacional.\n\n### 2. Rentabilidad del alquiler vacacional\n\nLos condominios bien ubicados en Punta Cana generan entre un 8% y 12% de rentabilidad anual neta a traves de plataformas como Airbnb y Booking.\n\n### 3. Apreciacion del valor\n\nEl precio del metro cuadrado en zonas premium como Cap Cana y Bavaro ha crecido entre un 8% y 15% anual en los ultimos 5 anos.\n\n### 4. Ley CONFOTUR\n\nEsta ley exime a los compradores de propiedades en proyectos turisticos del pago de impuestos de transferencia (3%) y del Impuesto al Patrimonio Inmobiliario (IPI) durante 15 anos.\n\n### 5. Estabilidad politica y economica\n\nRepublica Dominicana ha mantenido un crecimiento del PIB consistente, superiores al 4% anual, con una de las economias mas estables de la region.\n\n### 6. Infraestructura en expansion\n\nNuevas autopistas, el aeropuerto internacional de Punta Cana (el mas transitado del Caribe), y desarrollos de lujo continuan expandiendo la oferta.\n\n### 7. Facilidad para extranjeros\n\nNo hay restricciones para que extranjeros compren propiedades en RD. El proceso es transparente y el titulo de propiedad es definitivo.\n\n## Conclusion\n\nSi estas buscando diversificar tu portafolio con bienes raices en el Caribe, Punta Cana ofrece una combinacion unica de rentabilidad, apreciacion y calidad de vida.",
            'body_en' => "## Why Punta Cana is the Caribbean's investment gem\n\nPunta Cana isn't just a world-class tourist destination. It's one of the most dynamic and profitable real estate markets in Latin America. In 2026, the reasons to invest here are stronger than ever.\n\n### 1. Record tourism numbers\n\nThe Dominican Republic received over 10 million tourists in 2025, with Punta Cana concentrating more than 60% of that flow. This translates into constant demand for vacation accommodation.\n\n### 2. Vacation rental profitability\n\nWell-located condominiums in Punta Cana generate between 8% and 12% net annual return through platforms like Airbnb and Booking.\n\n### 3. Value appreciation\n\nThe price per square meter in premium areas like Cap Cana and Bavaro has grown between 8% and 15% annually over the last 5 years.\n\n### 4. CONFOTUR Law\n\nThis law exempts property buyers in tourism projects from transfer tax (3%) and Real Estate Tax (IPI) for 15 years.\n\n### 5. Political and economic stability\n\nThe Dominican Republic has maintained consistent GDP growth above 4% annually, with one of the most stable economies in the region.\n\n### 6. Expanding infrastructure\n\nNew highways, the Punta Cana international airport (the busiest in the Caribbean), and luxury developments continue to expand the offering.\n\n### 7. Easy for foreigners\n\nThere are no restrictions for foreigners buying property in DR. The process is transparent and property titles are definitive.\n\n## Conclusion\n\nIf you're looking to diversify your portfolio with Caribbean real estate, Punta Cana offers a unique combination of profitability, appreciation, and quality of life.",
            'category_id' => $guiaInversion?->id,
            'author_id' => $author?->id,
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'meta_title' => 'Invertir en Punta Cana 2026 - 7 Razones Clave | Real3D',
            'meta_title_en' => 'Invest in Punta Cana 2026 - 7 Key Reasons | Real3D',
            'meta_description' => 'Descubre las 7 razones clave para invertir en Punta Cana en 2026. Rentabilidad, apreciacion, ley CONFOTUR y mas.',
            'meta_description_en' => 'Discover 7 key reasons to invest in Punta Cana in 2026. Profitability, appreciation, CONFOTUR law and more.',
            'meta_keywords' => 'invertir punta cana, inversion inmobiliaria rd, punta cana 2026',
            'is_featured' => true,
        ]);
        $post1->tags()->sync(BlogTag::whereIn('slug', ['punta-cana', 'inversion', 'confotur'])->pluck('id'));

        // Post 2
        $post2 = BlogPost::firstOrCreate(['slug' => 'guia-comprar-propiedad-rd-extranjero'], [
            'title' => 'Guia completa: como comprar propiedad en RD siendo extranjero',
            'title_en' => 'Complete guide: how to buy property in DR as a foreigner',
            'slug' => 'guia-comprar-propiedad-rd-extranjero',
            'excerpt' => 'Todo lo que necesitas saber sobre el proceso de compra de propiedades en Republica Dominicana como inversor extranjero.',
            'excerpt_en' => 'Everything you need to know about the property buying process in the Dominican Republic as a foreign investor.',
            'body' => "## Comprar propiedad en RD como extranjero: paso a paso\n\nRepublica Dominicana es uno de los paises del Caribe mas amigables para inversores extranjeros. No existen restricciones de nacionalidad para adquirir bienes raices.\n\n### Paso 1: Elegir la propiedad\n\nInvestiga las zonas, visita (o usa herramientas 3D como Real3D), y compara opciones.\n\n### Paso 2: Reservar\n\nUna vez seleccionada la propiedad, se firma un contrato de reserva con un deposito tipico de USD 2,000 a 5,000.\n\n### Paso 3: Contrato de compraventa\n\nSe firma ante notario publico. Normalmente incluye un plan de pagos durante la construccion.\n\n### Paso 4: Due diligence\n\nUn abogado verifica el titulo de propiedad, gravamenes, y permisos de construccion.\n\n### Paso 5: Pago y transferencia\n\nSe realizan los pagos segun el plan acordado. Al completar, se realiza la transferencia del titulo.\n\n### Paso 6: Registro del titulo\n\nEl titulo se registra a nombre del comprador en el Registro de Titulos. Este proceso toma 2-4 semanas.\n\n### Costos adicionales\n\n- Impuesto de transferencia: 3% (exento con CONFOTUR)\n- Honorarios abogado: 1-1.5%\n- Gastos notariales y registro: ~1%\n\n### Documentos necesarios\n\n- Pasaporte vigente\n- Comprobante de fondos\n- Cedula fiscal (RNC) - se obtiene facilmente\n\n## Conclusion\n\nEl proceso es directo y transparente. Con la asesoria correcta, comprar propiedad en RD es seguro y eficiente.",
            'body_en' => "## Buying property in DR as a foreigner: step by step\n\nThe Dominican Republic is one of the most foreigner-friendly Caribbean countries for real estate investment. There are no nationality restrictions for acquiring real estate.\n\n### Step 1: Choose the property\n\nResearch areas, visit (or use 3D tools like Real3D), and compare options.\n\n### Step 2: Reserve\n\nOnce selected, a reservation agreement is signed with a typical deposit of USD 2,000 to 5,000.\n\n### Step 3: Purchase agreement\n\nSigned before a notary public. It usually includes a payment plan during construction.\n\n### Step 4: Due diligence\n\nA lawyer verifies the property title, liens, and construction permits.\n\n### Step 5: Payment and transfer\n\nPayments are made according to the agreed plan. Upon completion, the title transfer is executed.\n\n### Step 6: Title registration\n\nThe title is registered in the buyer's name at the Title Registry. This process takes 2-4 weeks.\n\n### Additional costs\n\n- Transfer tax: 3% (exempt with CONFOTUR)\n- Attorney fees: 1-1.5%\n- Notary and registration costs: ~1%\n\n### Required documents\n\n- Valid passport\n- Proof of funds\n- Tax ID (RNC) - easily obtained\n\n## Conclusion\n\nThe process is straightforward and transparent. With proper legal guidance, buying property in DR is safe and efficient.",
            'category_id' => $legalFiscal?->id,
            'author_id' => $author?->id,
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'meta_title' => 'Como Comprar Propiedad en RD siendo Extranjero | Real3D',
            'meta_title_en' => 'How to Buy Property in DR as a Foreigner | Real3D',
            'meta_description' => 'Guia paso a paso para comprar propiedad en Republica Dominicana como inversor extranjero. Proceso, costos y documentos.',
            'meta_description_en' => 'Step by step guide to buying property in the Dominican Republic as a foreign investor. Process, costs and documents.',
            'meta_keywords' => 'comprar propiedad rd extranjero, inversion rd, titulo propiedad dominicana',
            'is_featured' => true,
        ]);
        $post2->tags()->sync(BlogTag::whereIn('slug', ['extranjeros', 'inversion', 'impuestos'])->pluck('id'));

        // Post 3
        $post3 = BlogPost::firstOrCreate(['slug' => 'rentabilidad-alquiler-vacacional-punta-cana'], [
            'title' => 'Rentabilidad del alquiler vacacional en Punta Cana: numeros reales',
            'title_en' => 'Vacation rental profitability in Punta Cana: real numbers',
            'slug' => 'rentabilidad-alquiler-vacacional-punta-cana',
            'excerpt' => 'Analizamos los numeros reales de rentabilidad del alquiler vacacional en Punta Cana con datos de 2025-2026.',
            'excerpt_en' => 'We analyze the real profitability numbers of vacation rental in Punta Cana with 2025-2026 data.',
            'body' => "## Numeros reales del alquiler vacacional en Punta Cana\n\nUna de las preguntas mas frecuentes de los inversores es: cual es la rentabilidad real? Vamos a los numeros.\n\n### Tarifa promedio por noche\n\n- Estudio/1 hab: USD 80-120/noche\n- 2 habitaciones: USD 120-180/noche\n- 3 habitaciones (lujo): USD 200-350/noche\n\n### Ocupacion promedio\n\n- Temporada alta (dic-abril): 80-95%\n- Temporada media (mayo-julio, nov): 60-75%\n- Temporada baja (agosto-oct): 45-60%\n- **Promedio anual: 65-75%**\n\n### Ejemplo practico: apartamento 2 hab en Bavaro\n\n- Valor: USD 180,000\n- Tarifa promedio: USD 140/noche\n- Ocupacion: 70% (256 noches)\n- **Ingreso bruto: USD 35,840/ano**\n- Gastos operativos (25%): -USD 8,960\n- Mantenimiento y HOA: -USD 3,600\n- **Ingreso neto: USD 23,280/ano**\n- **Rentabilidad neta: 12.9%**\n\n### Factores que afectan la rentabilidad\n\n1. **Ubicacion**: Primera linea de playa vs interior\n2. **Calidad del amueblado**: Inversion en decoracion se paga sola\n3. **Gestion profesional**: Property managers cobran 15-25% pero optimizan ocupacion\n4. **Reviews**: La reputacion en Airbnb es todo\n\n### Comparacion con otras inversiones\n\n| Inversion | Rentabilidad anual |\n|-----------|-------------------|\n| Alquiler Punta Cana | 8-13% |\n| S&P 500 (promedio) | 7-10% |\n| Bonos del tesoro | 4-5% |\n| Alquiler Madrid | 3-5% |\n| Alquiler Miami | 4-6% |\n\n## Conclusion\n\nLos numeros hablan por si solos. Punta Cana ofrece una de las mejores relaciones riesgo-rentabilidad en bienes raices a nivel global.",
            'body_en' => "## Real vacation rental numbers in Punta Cana\n\nOne of the most frequent questions from investors is: what's the real return? Let's look at the numbers.\n\n### Average nightly rate\n\n- Studio/1 bed: USD 80-120/night\n- 2 bedrooms: USD 120-180/night\n- 3 bedrooms (luxury): USD 200-350/night\n\n### Average occupancy\n\n- High season (Dec-April): 80-95%\n- Mid season (May-July, Nov): 60-75%\n- Low season (August-Oct): 45-60%\n- **Annual average: 65-75%**\n\n### Practical example: 2-bed apartment in Bavaro\n\n- Value: USD 180,000\n- Average rate: USD 140/night\n- Occupancy: 70% (256 nights)\n- **Gross income: USD 35,840/year**\n- Operating expenses (25%): -USD 8,960\n- Maintenance and HOA: -USD 3,600\n- **Net income: USD 23,280/year**\n- **Net yield: 12.9%**\n\n### Factors affecting profitability\n\n1. **Location**: Beachfront vs inland\n2. **Furnishing quality**: Investment in decor pays for itself\n3. **Professional management**: Property managers charge 15-25% but optimize occupancy\n4. **Reviews**: Airbnb reputation is everything\n\n### Comparison with other investments\n\n| Investment | Annual return |\n|-----------|-------------------|\n| Punta Cana rental | 8-13% |\n| S&P 500 (average) | 7-10% |\n| Treasury bonds | 4-5% |\n| Madrid rental | 3-5% |\n| Miami rental | 4-6% |\n\n## Conclusion\n\nThe numbers speak for themselves. Punta Cana offers one of the best risk-return ratios in global real estate.",
            'category_id' => $turismoRentabilidad?->id,
            'author_id' => $author?->id,
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'meta_title' => 'Rentabilidad Alquiler Vacacional Punta Cana 2026 | Real3D',
            'meta_title_en' => 'Vacation Rental Profitability Punta Cana 2026 | Real3D',
            'meta_description' => 'Numeros reales de rentabilidad del alquiler vacacional en Punta Cana. Tarifas, ocupacion y comparativas con datos 2025-2026.',
            'meta_description_en' => 'Real profitability numbers for vacation rental in Punta Cana. Rates, occupancy and comparisons with 2025-2026 data.',
            'meta_keywords' => 'rentabilidad alquiler punta cana, airbnb punta cana, ROI inmobiliario caribe',
            'is_featured' => false,
        ]);
        $post3->tags()->sync(BlogTag::whereIn('slug', ['punta-cana', 'rentabilidad', 'airbnb'])->pluck('id'));
    }
}
