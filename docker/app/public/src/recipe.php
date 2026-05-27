<?php
    //after authorisation, the check to make sure recipe ID is valid etc.

    $recipe = [
        'title' => 'Crack Burger',
        'kcal' => 720,
        'time' => '20 minutes',
        'description' => 'A rich, indulgent smash-style burger loaded with crispy bacon, melted cheddar, tangy sour cream and a bold ranch kick. Perfect for a weekend treat.',
        'tags' => ['American', 'Burger'],
        'image' => '../images/recipes/crack_burger.jpg',
        'ingredients' => [
            ['name' => 'Ground beef', 'amount' => '680 g'],
            ['name' => 'Cooked and crumbled bacon slices', 'amount' => '35 g'],
            ['name' => 'Shredded sharp cheddar cheese', 'amount' => '56 g'],
            ['name' => 'Sour cream', 'amount' => '45 g'],
            ['name' => 'Ranch seasoning mix', 'amount' => '14 g'],
            ['name' => 'Worcestershire sauce', 'amount' => '5 g'],
            ['name' => 'Salt & pepper', 'amount' => ''],
        ],
        'steps' => [
            [
                'number' => 1,
                'text' => 'In a large bowl, combine all patty ingredients. Gently knead with your hands until just combined; don\'t overwork the meat. Divide into 8 loose 3-ounce balls (weigh if possible) and keep chilled.',
            ],
            [
                'number' => 2,
                'text' => 'Heat a cast-iron skillet or griddle over high heat until smoking. Place a ball of meat onto the surface and smash flat with a spatula. Season with salt and pepper. Cook for 2 minutes.',
            ],
            [
                'number' => 3,
                'text' => 'Flip each patty, immediately add cheese on top, and cook for another 1-2 minutes until the cheese melts. Stack two patties per burger.',
            ],
            [
                'number' => 4,
                'text' => 'Toast the buns cut-side down on the skillet. Spread sour cream on the bottom bun, add the stacked patties, top with bacon and your preferred toppings. Serve immediately.',
            ],
        ],
    ];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECIPE - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <link rel="stylesheet" href="../css/recipe.css">
</head>
<body>
    <div id="page-wrapper">
        <div class="page-section">HEADER</div>
        <div class="page-section">PIC</div>
        <div class="page-section">YAP</div>
    </div>
</body>
</html>