<?php
require_once 'config.php';

// Sample posts from different fisherfolk users
$posts = [
    [2, "Good morning fellow fisherfolks! Just got back from a great catch today. The sea was calm and the fish were biting! 🐟🎣", null, '2026-05-06 06:30:00'],
    [3, "Fresh fish available at Poblacion market! Come visit my stall. We have bangus, tilapia, and galunggong at great prices! 🐠", null, '2026-05-06 07:15:00'],
    [4, "Warning to all fishermen: Strong currents expected near Punta area this week. Please be careful and always wear your life vests. Stay safe everyone! ⚠️🌊", null, '2026-05-05 18:00:00'],
    [5, "Just finished repairing my fishing net. Ready for another productive week! Does anyone know where to buy affordable nylon thread? 🪡", null, '2026-05-05 14:30:00'],
    [6, "Congratulations to all who received their new Fisherfolk IDs! The process was smooth and the office staff were very helpful. Thank you LGU! 🎉👏", null, '2026-05-05 10:00:00'],
    [2, "Sunset at the bay today was breathtaking. This is why I love being a fisherman. No office view can beat this! 🌅", null, '2026-05-04 17:45:00'],
    [3, "Reminder: The municipal government is offering free boat registration renewal until end of May. Don't miss out! 📋", null, '2026-05-04 09:00:00'],
    [4, "Had a great time at the Fisherfolk Barangay Assembly last night. Lots of important updates about subsidies and new programs. 📢", null, '2026-05-03 20:30:00'],
    [5, "My aquaculture pond is doing well this season! The tilapia are growing fast. Hoping for a good harvest next month. 🐟🌿", null, '2026-05-03 11:00:00'],
    [6, "To all fisherfolk: Please remember to report any boat damage subsidy claims through the system. The admin team is very responsive! 💪", null, '2026-05-02 15:00:00'],
    [2, "Just attended the safety training organized by the coast guard. Very informative! Every fisherman should attend these sessions. 🛟", null, '2026-05-02 08:00:00'],
    [3, "Best catch of the week! 50kg of lapu-lapu! Hard work really pays off. Sharing some with the neighbors tonight. 🎣🏆", null, '2026-05-01 16:00:00'],
    [4, "Anyone else having trouble with their fishing permits? The online system seems to be down. Let me know if you need help! 🤔", null, '2026-05-01 10:30:00'],
    [5, "Happy Labor Day to all hardworking fisherfolk! We feed the nation and we should be proud of what we do! 💪🇵🇭", null, '2026-05-01 06:00:00'],
    [6, "Tips for new fishermen: Always check the weather forecast before heading out. PAGASA updates are your best friend! ⛅🌧️", null, '2026-04-30 12:00:00'],
];

// Sample comments
$comments = [
    // post_id will be set dynamically
    ["Great catch! How long were you out?", 3],
    ["Stay safe out there!", 5],
    ["Thanks for the tip!", 6],
    ["That sounds amazing!", 2],
    ["I agree, the staff were very helpful!", 4],
    ["Beautiful photo! Where was this taken?", 3],
    ["Thank you for sharing this info!", 5],
    ["Wow, 50kg! That's incredible!", 2],
    ["Happy Labor Day to you too!", 6],
    ["Very important reminder, thank you!", 4],
    ["The weather has been unpredictable lately.", 3],
    ["I was there too! Great assembly.", 2],
    ["Good luck with your harvest!", 6],
    ["Where can we sign up for the training?", 5],
    ["Fresh bangus is my favorite!", 4],
];

// Sample reactions
$reactionTypes = ['like', 'like', 'like', 'love', 'like'];

echo "<h2>Populating Community Feed...</h2>";

// Insert posts
$postIds = [];
foreach ($posts as $p) {
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_path, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$p[0], $p[1], $p[2], $p[3]]);
        $postIds[] = $pdo->lastInsertId();
        echo "✅ Post by user {$p[0]} added<br>";
    } catch (Exception $e) {
        echo "⚠️ Post error: " . $e->getMessage() . "<br>";
    }
}

// Insert comments (spread across posts)
$commentIndex = 0;
foreach ($postIds as $idx => $postId) {
    // Add 1-2 comments per post
    $numComments = ($idx % 3 == 0) ? 2 : 1;
    for ($i = 0; $i < $numComments && $commentIndex < count($comments); $i++) {
        $c = $comments[$commentIndex];
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL ? MINUTE))");
            $stmt->execute([$postId, $c[1], $c[0], $posts[$idx][3], rand(10, 120)]);
            echo "💬 Comment on post #{$postId} added<br>";
        } catch (Exception $e) {
            echo "⚠️ Comment error: " . $e->getMessage() . "<br>";
        }
        $commentIndex++;
    }
}

// Insert reactions (3-6 likes per post from random users)
$userIds = [2, 3, 4, 5, 6, 13];
foreach ($postIds as $postId) {
    $numReactions = rand(3, 6);
    shuffle($userIds);
    for ($i = 0; $i < $numReactions; $i++) {
        try {
            $reaction = $reactionTypes[array_rand($reactionTypes)];
            $stmt = $pdo->prepare("INSERT IGNORE INTO reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $userIds[$i], $reaction]);
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    echo "👍 Reactions added to post #{$postId}<br>";
}

echo "<br><h3>✅ Done! Community feed is now populated.</h3>";
echo "<p><a href='index.php'>← Go back to Community Feed</a></p>";
?>
