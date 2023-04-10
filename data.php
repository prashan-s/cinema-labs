<?php
// data.php

// Data for the main carousel
$carousel_slides = [
    [
        'id' => 'dune-part-two',
        'title' => 'Dune: Part Two',
        'year' => '2024',
        'genre' => 'Sci-Fi',
        'rating' => '8.5',
        'duration' => '166 min',
        'description' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.',
        'cast' => ['Timothée Chalamet', 'Zendaya', 'Rebecca Ferguson', 'Javier Bardem'],
        'director' => 'Denis Villeneuve',
        'image_url' => 'https://image.tmdb.org/t/p/original/kGzFbGhp99zva6oZODW5atUtnqi.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/kGzFbGhp99zva6oZODW5atUtnqi.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'oppenheimer',
        'title' => 'Oppenheimer',
        'year' => '2023',
        'genre' => 'Biography',
        'rating' => '8.3',
        'duration' => '180 min',
        'description' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',
        'cast' => ['Cillian Murphy', 'Emily Blunt', 'Matt Damon', 'Robert Downey Jr.'],
        'director' => 'Christopher Nolan',
        'image_url' => 'https://image.tmdb.org/t/p/original/fm6KqXpk3M2HVveHwCrBSSBaO0V.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/fm6KqXpk3M2HVveHwCrBSSBaO0V.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'blade-runner-2049',
        'title' => 'Blade Runner 2049',
        'year' => '2017',
        'genre' => 'Sci-Fi',
        'rating' => '8.0',
        'duration' => '164 min',
        'description' => 'A young blade runner discovers a long-buried secret that has the potential to plunge what is left of society into chaos.',
        'cast' => ['Ryan Gosling', 'Harrison Ford', 'Ana de Armas', 'Sylvia Hoeks'],
        'director' => 'Denis Villeneuve',
        'image_url' => 'https://image.tmdb.org/t/p/original/jsoz1HdU2EbbdghEXJALiXOVbN.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/jsoz1HdU2EbbdghEXJALiXOVbN.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'interstellar',
        'title' => 'Interstellar',
        'year' => '2014',
        'genre' => 'Sci-Fi',
        'rating' => '8.7',
        'duration' => '169 min',
        'description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
        'cast' => ['Matthew McConaughey', 'Anne Hathaway', 'Jessica Chastain', 'Bill Irwin'],
        'director' => 'Christopher Nolan',
        'image_url' => 'https://image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/xJHokMbljvjADYdit5fK5VQsXEG.jpg',
        'type' => 'movie'
    ]
];

// Data for the Trending Movies grid
$trending_movies = [
    [
        'id' => 'super-mario-bros-movie',
        'title' => 'The Super Mario Bros. Movie',
        'year' => '2023',
        'genre' => 'Animation',
        'rating' => '7.0',
        'duration' => '92 min',
        'description' => 'A plumber named Mario travels through an underground labyrinth with his brother Luigi, trying to save a captured princess.',
        'cast' => ['Chris Pratt', 'Anya Taylor-Joy', 'Charlie Day', 'Jack Black'],
        'director' => 'Aaron Horvath',
        'image_url' => 'https://image.tmdb.org/t/p/w500/qVdr2VOFq7QfKovteVj5gX8YvAN.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/qVdr2VOFq7QfKovteVj5gX8YvAN.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'oppenheimer-trending',
        'title' => 'Oppenheimer',
        'year' => '2023',
        'genre' => 'Biography',
        'rating' => '8.3',
        'duration' => '180 min',
        'description' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',
        'cast' => ['Cillian Murphy', 'Emily Blunt', 'Matt Damon', 'Robert Downey Jr.'],
        'director' => 'Christopher Nolan',
        'image_url' => 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'interstellar-trending',
        'title' => 'Interstellar',
        'year' => '2014',
        'genre' => 'Sci-Fi',
        'rating' => '8.7',
        'duration' => '169 min',
        'description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
        'cast' => ['Matthew McConaughey', 'Anne Hathaway', 'Jessica Chastain', 'Bill Irwin'],
        'director' => 'Christopher Nolan',
        'image_url' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'evil-dead-rise',
        'title' => 'Evil Dead Rise',
        'year' => '2023',
        'genre' => 'Horror',
        'rating' => '6.5',
        'duration' => '96 min',
        'description' => 'A twisted tale of two estranged sisters whose reunion is cut short by the rise of flesh-possessing demons.',
        'cast' => ['Lily Sullivan', 'Alyssa Sutherland', 'Morgan Davies', 'Gabrielle Echols'],
        'director' => 'Lee Cronin',
        'image_url' => 'https://image.tmdb.org/t/p/w500/xYEP9RW20sY8PpeetGkF0gM2gNq.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/xYEP9RW20sY8PpeetGkF0gM2gNq.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'ferrari',
        'title' => 'Ferrari',
        'year' => '2023',
        'genre' => 'Biography',
        'rating' => '6.5',
        'duration' => '131 min',
        'description' => 'Set during the summer of 1957, Ex-race car driver, Enzo Ferrari, is in crisis.',
        'cast' => ['Adam Driver', 'Penélope Cruz', 'Shailene Woodley', 'Patrick Dempsey'],
        'director' => 'Michael Mann',
        'image_url' => 'https://image.tmdb.org/t/p/w500/lQG0VVTDPa13T9i2ltiGrj2p5lD.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/lQG0VVTDPa13T9i2ltiGrj2p5lD.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'kung-fu-panda-4',
        'title' => 'Kung Fu Panda 4',
        'year' => '2024',
        'genre' => 'Animation',
        'rating' => '6.3',
        'duration' => '94 min',
        'description' => 'After Po is tapped to become the Spiritual Leader of the Valley of Peace, he needs to find and train a new Dragon Warrior.',
        'cast' => ['Jack Black', 'Awkwafina', 'Viola Davis', 'Dustin Hoffman'],
        'director' => 'Mike Mitchell',
        'image_url' => 'https://image.tmdb.org/t/p/w500/bdaApsT6iV5vHY2Vf2GfW2R7b5u.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/bdaApsT6iV5vHY2Vf2GfW2R7b5u.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'the-beekeeper',
        'title' => 'The Beekeeper',
        'year' => '2024',
        'genre' => 'Action',
        'rating' => '6.4',
        'duration' => '105 min',
        'description' => 'One man\'s campaign for vengeance takes on national stakes after he is revealed to be a former operative of a powerful and clandestine organization known as Beekeepers.',
        'cast' => ['Jason Statham', 'Emmy Raver-Lampman', 'Josh Hutcherson', 'Bobby Naderi'],
        'director' => 'David Ayer',
        'image_url' => 'https://image.tmdb.org/t/p/w500/A7EByudX0eOzlkQ2FIbogzyazm2.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/A7EByudX0eOzlkQ2FIbogzyazm2.jpg',
        'type' => 'movie'
    ],
    [
        'id' => 'outer-range',
        'title' => 'Outer Range',
        'year' => '2022',
        'genre' => 'Sci-Fi',
        'rating' => '7.0',
        'duration' => '2 Seasons',
        'description' => 'A rancher fighting for his land and family discovers an unfathomable mystery at the edge of Wyoming\'s wilderness.',
        'cast' => ['Josh Brolin', 'Imogen Poots', 'Lili Taylor', 'Tom Pelphrey'],
        'creator' => 'Brian Watkins',
        'image_url' => 'https://image.tmdb.org/t/p/w500/29rhl1xSR0UEV2o1Ppe2IuSj277.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/29rhl1xSR0UEV2o1Ppe2IuSj277.jpg',
        'type' => 'show'
    ]
];

// Data for the Trending TV Shows grid
$trending_shows = [
    [
        'id' => 'halo',
        'title' => 'Halo',
        'year' => '2022',
        'genre' => 'Sci-Fi',
        'rating' => '7.0',
        'duration' => '2 Seasons',
        'description' => 'With the galaxy on the brink of destruction, Master Chief leads his team of Spartans against the alien threat known as the Covenant.',
        'cast' => ['Pablo Schreiber', 'Jen Taylor', 'Yerin Ha', 'Natascha McElhone'],
        'creator' => 'Kyle Killen',
        'image_url' => 'https://image.tmdb.org/t/p/w500/nCzzQKGwv5kxcDxxMpk3XpTa4ar.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/nCzzQKGwv5kxcDxxMpk3XpTa4ar.jpg',
        'type' => 'show'
    ],
    [
        'id' => 'game-of-thrones',
        'title' => 'Game of Thrones',
        'year' => '2011',
        'genre' => 'Fantasy',
        'rating' => '9.2',
        'duration' => '8 Seasons',
        'description' => 'Nine noble families fight for control over the lands of Westeros, while an ancient enemy returns after being dormant for millennia.',
        'cast' => ['Sean Bean', 'Mark Addy', 'Nikolaj Coster-Waldau', 'Michelle Fairley'],
        'creator' => 'David Benioff',
        'image_url' => 'https://image.tmdb.org/t/p/w500/u3bZgnGQ9T01sWNhyveQz0wH0Hl.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/u3bZgnGQ9T01sWNhyveQz0wH0Hl.jpg',
        'type' => 'show'
    ],
    [
        'id' => '3-body-problem',
        'title' => '3 Body Problem',
        'year' => '2024',
        'genre' => 'Sci-Fi',
        'rating' => '7.5',
        'duration' => '1 Season',
        'description' => 'A fateful decision in 1960s China echoes across space and time to a group of scientists in the present, forcing them to face humanity\'s greatest threat.',
        'cast' => ['Jovan Adepo', 'Liam Cunningham', 'Eiza González', 'Jess Hong'],
        'creator' => 'David Benioff',
        'image_url' => 'https://image.tmdb.org/t/p/w500/hYqgoiK5k1QoP8a72zRi0gDoT3Y.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/hYqgoiK5k1QoP8a72zRi0gDoT3Y.jpg',
        'type' => 'show'
    ],
    [
        'id' => 'warrior',
        'title' => 'Warrior',
        'year' => '2019',
        'genre' => 'Action',
        'rating' => '8.4',
        'duration' => '3 Seasons',
        'description' => 'A gritty, action-packed crime drama set during the brutal Tong Wars of San Francisco\'s Chinatown in the second half of the 19th century.',
        'cast' => ['Andrew Koji', 'Olivia Cheng', 'Jason Tobin', 'Dianne Doan'],
        'creator' => 'Jonathan Tropper',
        'image_url' => 'https://image.tmdb.org/t/p/w500/fS4i7nOrQ1Y3f02p22aOa5sV882.jpg',
        'poster_url' => 'https://image.tmdb.org/t/p/w500/fS4i7nOrQ1Y3f02p22aOa5sV882.jpg',
        'type' => 'show'
    ]
];

// Function to get all content (movies and shows combined)
function getAllContent() {
    global $carousel_slides, $trending_movies, $trending_shows;
    return array_merge($carousel_slides, $trending_movies, $trending_shows);
}

// Function to find content by ID
function findContentById($id) {
    $allContent = getAllContent();
    foreach ($allContent as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}