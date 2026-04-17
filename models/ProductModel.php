<?php
/**
 * ProductModel — handles Product table operations
 */
class ProductModel extends Model
{
    /** Get all products with their category name */
    public function getAll(): array
    {
        return $this->db->read(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             ORDER BY c.Name, p.Name"
        );
    }

    /** Get only active products */
    public function getActive(): array
    {
        return $this->db->read(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE p.Active = 1
             ORDER BY c.Name, p.Name"
        );
    }

    /** Get active products grouped by category */
    public function getActiveGrouped(): array
    {
        $products = $this->getActive();
        $grouped = [];
        foreach ($products as $product) {
            $grouped[$product['Category_Name']][] = $product;
        }
        return $grouped;
    }

    /** Get active products as a flat JSON-safe array keyed by Product_ID */
    public function getActiveAsMap(): array
    {
        $products = $this->getActive();
        $map = [];
        foreach ($products as $p) {
            $map[$p['Product_ID']] = [
                'Product_ID'    => (int) $p['Product_ID'],
                'Name'          => $p['Name'],
                'Category_Name' => $p['Category_Name'],
                'Unit'          => $p['Unit'],
                'Price'         => (float) $p['Price'],
                'Image'         => self::getProductImagePath($p['Name']),
            ];
        }
        return $map;
    }

    /**
     * Explicit map from product name -> path (relative to assets/img/products/).
     *
     * Real image filenames don't all follow the slug convention yet (some use
     * camelCase, some include spaces, some live in category subfolders), so
     * this table links each product name to its actual file. Products added
     * later that DO follow the slug convention ({slug}.jpg flat in the folder)
     * will be picked up automatically by the fallback resolver below.
     */
    private static array $imageMap = [
        // Burgers
        'All Around Burger'              => 'Burgers/All Around Burger.png',
        'Burger with Cheese'             => 'Burgers/burgerCheese.png',
        'Burger with Egg & Cheese'       => 'Burgers/burgerEggNCheese.png',
        'Burger with Ham & Cheese'       => 'Burgers/burgerHamNCheese.png',
        'Burger with Ham & Egg'          => 'Burgers/burgerHamNEgg.png',
        'Burger Patty'                   => 'Burgers/burgerPatty.png',
        'Burger Patty with Egg & Cheese' => 'Burgers/burgerPattyEggNCheese.png',
        'Burger Patty with Cheese'       => 'Burgers/burgerPattyNCheese.png',
        'Burger Patty with Egg'          => 'Burgers/burgerPattyNEgg.png',
        // Drinks
        'Caramel Coffee'                 => 'Drinks/CaramelCoffee.png',
        'Coke Swakto'                    => 'Drinks/CokeSwakto.png',
        'Iced Coffee'                    => 'Drinks/IcedCoffee.png',
        'Iced Matcha'                    => 'Drinks/IcedMatcha.png',
        'Royal Swakto'                   => 'Drinks/RoyalSwakto.png',
        'Sprite Swakto'                  => 'Drinks/SpriteSwakto.png',
        'Sting'                          => 'Drinks/Sting.png',
        'Coke 500ml'                     => 'Drinks/coke500ml.png',
        'Royal 500ml'                    => 'Drinks/royal500ml.png',
        'Sprite 500ml'                   => 'Drinks/sprite500ml.png',
        // Hotdogs
        'Hungarian Hotdog'               => 'Hotdogs/Hungarian Hotdog.png',
        // Ricebowl
        'Cup of Rice'                    => 'Ricebowl/cupOfRice.png',
        'Egg'                            => 'Ricebowl/egg.png',
        'Lumpia Bowl'                    => 'Ricebowl/lumpiaBowl.png',
        'Siomai Bowl'                    => 'Ricebowl/siomaiBowl.png',
        'Sisig Bowl'                     => 'Ricebowl/sisigBowl.png',
        // Snacks
        'Canton'                         => 'Snacks/cantonSnack.png',
        'Fish Balls'                     => 'Snacks/fisballsSnack.png',
        'Fries'                          => 'Snacks/friesSnack.png',
        'Kikiam'                         => 'Snacks/kikiamSnack.png',
        'Siomai'                         => 'Snacks/siomaiSnack.png',
        'Siopao'                         => 'Snacks/siopaoSnack.png',
    ];

    /**
     * Resolve the image URL for a product by name.
     *
     * Resolution order:
     *   1. Explicit entry in self::$imageMap (exact product name match).
     *   2. Slug-based auto-detect in assets/img/products/ for common
     *      extensions (jpg, jpeg, png, webp). Lets future products drop
     *      in without code changes as long as they follow the slug rule.
     *   3. placehold.co URL showing the product name as a safe fallback.
     *
     * Slug rule: lowercase, non-alphanumeric sequences collapsed to hyphens,
     *            trimmed. Example: "Chicken Breast" -> chicken-breast
     */
    public static function getProductImagePath(string $productName): string
    {
        $baseDir = __DIR__ . '/../assets/img/products/';

        // 1. Explicit map
        if (isset(self::$imageMap[$productName])) {
            $relative = 'assets/img/products/' . self::$imageMap[$productName];
            if (file_exists(__DIR__ . '/../' . $relative)) {
                return self::toPublicUrl($relative);
            }
        }

        // 2. Slug-based auto-detect
        $slug = strtolower($productName);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug !== '') {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $relative = "assets/img/products/{$slug}.{$ext}";
                if (file_exists(__DIR__ . '/../' . $relative)) {
                    return self::toPublicUrl($relative);
                }
            }
        }

        // 3. Placeholder
        return 'https://placehold.co/200x200?text=' . rawurlencode($productName);
    }

    /** Convert a relative path into a URL-encoded public URL (preserves slashes) */
    private static function toPublicUrl(string $relative): string
    {
        $parts = explode('/', $relative);
        $encoded = array_map('rawurlencode', $parts);
        return BASE_URL . '/' . implode('/', $encoded);
    }

    /** Find a product by ID */
    public function findById(int $product_id): ?array
    {
        return $this->db->readOne(
            "SELECT p.*, c.Name AS Category_Name
             FROM Product p
             JOIN Category c ON p.Category_ID = c.Category_ID
             WHERE p.Product_ID = ?",
            [$product_id]
        );
    }

    /** Create a new product */
    public function create(int $category_id, string $name, string $unit, float $price): int
    {
        return $this->db->insert(
            "INSERT INTO Product (Category_ID, Name, Unit, Price) VALUES (?, ?, ?, ?)",
            [$category_id, $name, $unit, $price]
        );
    }

    /** Update a product */
    public function update(int $product_id, int $category_id, string $name, string $unit, float $price, bool $active): int
    {
        return $this->db->write(
            "UPDATE Product SET Category_ID = ?, Name = ?, Unit = ?, Price = ?, Active = ? WHERE Product_ID = ?",
            [$category_id, $name, $unit, $price, (int) $active, $product_id]
        );
    }
}
