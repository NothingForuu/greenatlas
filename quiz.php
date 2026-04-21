<?php
include "config/database.php";

// Fetch species with fun facts for quiz questions
$species = [];
$result = $conn->query("SELECT species_id, species_name, species_type, threat_level, fun_fact, image_path, scientific_name FROM species WHERE status='Approved' AND fun_fact IS NOT NULL AND fun_fact != '' ORDER BY RAND() LIMIT 40");
while ($row = $result->fetch_assoc()) {
    $species[] = $row;
}

// Need at least 10 species for a good quiz
if (count($species) < 5) {
    die("Not enough species data for quiz. Please add more species first.");
}

// Build 10 questions
$questions = [];
$used = [];
$count = min(10, count($species));

for ($i = 0; $i < $count; $i++) {
    $s = $species[$i];

    // Question types rotate
    $type = $i % 3;

    if ($type === 0) {
        // "Which animal has this fun fact?"
        $question = "Which animal is described by this fact?";
        $hint = '"' . $s['fun_fact'] . '"';
        $correct = $s['species_name'];
    } elseif ($type === 1) {
        // "What is the threat level of X?"
        $question = "What is the conservation status of the <strong>" . htmlspecialchars($s['species_name']) . "</strong>?";
        $hint = null;
        $correct = $s['threat_level'];
    } else {
        // "What type of animal is X?"
        $question = "What class of animal is the <strong>" . htmlspecialchars($s['species_name']) . "</strong>?";
        $hint = null;
        $correct = $s['species_type'];
    }

    // Build wrong answers
    $wrong_pool = [];
    if ($type === 0) {
        // Wrong species names
        foreach ($species as $other) {
            if ($other['species_name'] !== $correct) {
                $wrong_pool[] = $other['species_name'];
            }
        }
    } elseif ($type === 1) {
        $all_threats = ['Endangered', 'Vulnerable', 'Low', 'Near Threatened'];
        $wrong_pool = array_filter($all_threats, fn($t) => $t !== $correct);
    } else {
        $all_types = ['Mammal', 'Bird', 'Reptile', 'Amphibian', 'Marine', 'Fish'];
        $wrong_pool = array_filter($all_types, fn($t) => $t !== $correct);
    }

    shuffle($wrong_pool);
    $wrong = array_slice(array_values($wrong_pool), 0, 3);

    $options = array_merge([$correct], $wrong);
    shuffle($options);

    $questions[] = [
        'question' => $question,
        'hint'     => $hint,
        'options'  => $options,
        'correct'  => $correct,
        'image'    => $s['image_path'],
        'species'  => $s['species_name'],
        'id'       => $s['species_id']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wildlife Quiz — GreenAtlas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --gm: #52b788; --gl: #b7e4c7; --bg: #0b0f14;
            --card: rgba(255,255,255,0.04); --border: rgba(255,255,255,0.08);
            --text: #e6fff2; --muted: #8aab99;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* NAVBAR */
        .navbar {
            position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
            width: 95%; max-width: 1200px;
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 20px;
            background: rgba(11,15,20,.75); backdrop-filter: blur(16px);
            border-radius: 50px; border: 1px solid var(--border); z-index: 999;
        }

        .logo {
            font-size: 20px; font-weight: 700;
            background: linear-gradient(90deg,#00ffcc,#52b788);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-back {
            display: flex; align-items: center; gap: 8px;
            color: var(--gl); text-decoration: none; font-size: 14px;
            padding: 8px 16px; border-radius: 25px;
            border: 1px solid var(--border); background: var(--card); transition: .3s;
        }

        .nav-back:hover { background: rgba(82,183,136,.15); border-color: var(--gm); }

        /* HERO BANNER */
        .quiz-banner {
            padding: 120px 20px 40px;
            text-align: center;
        }

        .quiz-banner h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5vw, 56px);
            color: #fff;
            margin-bottom: 12px;
        }

        .quiz-banner p {
            color: var(--muted);
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* PROGRESS */
        .progress-wrap {
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .progress-track {
            height: 6px;
            background: rgba(255,255,255,0.08);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #52b788, #00ffcc);
            border-radius: 6px;
            transition: width 0.5s ease;
        }

        /* QUIZ CARD */
        .quiz-wrap {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }

        .question-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .question-image {
            width: 100%;
            height: 260px;
            object-fit: cover;
            display: block;
        }

        .question-body {
            padding: 28px;
        }

        .question-num {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gm);
            margin-bottom: 10px;
        }

        .question-text {
            font-size: 18px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .question-hint {
            font-size: 14px;
            color: var(--muted);
            font-style: italic;
            background: rgba(82,183,136,0.06);
            border-left: 3px solid var(--gm);
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        /* OPTIONS */
        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .option-btn {
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.03);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }

        .option-btn:hover:not(:disabled) {
            border-color: var(--gm);
            background: rgba(82,183,136,0.08);
            transform: translateY(-2px);
        }

        .option-btn.correct {
            border-color: #22c55e;
            background: rgba(34,197,94,0.15);
            color: #22c55e;
        }

        .option-btn.wrong {
            border-color: #ff4d4d;
            background: rgba(255,77,77,0.1);
            color: #ff4d4d;
        }

        .option-btn:disabled { cursor: default; }

        /* FEEDBACK */
        .feedback {
            display: none;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .feedback.correct {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
            display: block;
        }

        .feedback.wrong {
            background: rgba(255,77,77,0.1);
            border: 1px solid rgba(255,77,77,0.3);
            color: #ff4d4d;
            display: block;
        }

        /* NEXT BUTTON */
        .next-btn {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(90deg, #52b788, #40916c);
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: none;
            transition: 0.3s;
        }

        .next-btn:hover { opacity: 0.9; transform: translateY(-2px); }

        /* SCORE CARD */
        .score-card {
            display: none;
            text-align: center;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 50px 30px;
            animation: slideIn 0.5s ease;
        }

        .score-emoji { font-size: 64px; margin-bottom: 16px; }

        .score-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #fff;
            margin-bottom: 8px;
        }

        .score-subtitle { color: var(--muted); font-size: 16px; margin-bottom: 30px; }

        .score-big {
            font-size: 72px;
            font-weight: 700;
            color: var(--gm);
            line-height: 1;
            margin-bottom: 8px;
        }

        .score-out { font-size: 18px; color: var(--muted); margin-bottom: 30px; }

        .score-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        .score-btn {
            padding: 12px 28px;
            border-radius: 25px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: 0.3s;
        }

        .score-btn.primary {
            background: linear-gradient(90deg, #52b788, #40916c);
            color: white;
        }

        .score-btn.secondary {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--gl);
        }

        .score-btn:hover { transform: translateY(-2px); opacity: 0.9; }

        /* SCORE BREAKDOWN */
        .breakdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0 30px;
            flex-wrap: wrap;
        }

        .breakdown-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 20px;
            text-align: center;
            min-width: 90px;
        }

        .breakdown-num { font-size: 24px; font-weight: 700; }
        .breakdown-lbl { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }

        @media (max-width: 500px) {
            .options { grid-template-columns: 1fr; }
            .question-image { height: 180px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="logo">GreenAtlas 🌿</a>
    <a href="index.php" class="nav-back"><i class="fas fa-arrow-left"></i> Back</a>
</nav>

<!-- BANNER -->
<div class="quiz-banner">
    <h1>🦁 Wildlife Quiz</h1>
    <p>Test your knowledge of India's incredible wildlife — <?php echo count($questions); ?> questions await!</p>
</div>

<!-- PROGRESS -->
<div class="progress-wrap">
    <div class="progress-labels">
        <span id="prog-text">Question 1 of <?php echo count($questions); ?></span>
        <span id="score-live">Score: 0</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" id="prog-fill" style="width: 0%"></div>
    </div>
</div>

<!-- QUIZ -->
<div class="quiz-wrap">
    <div class="question-card" id="quiz-card">
        <img class="question-image" id="q-image" src="" onerror="this.src='uploads/default.jpg'" alt="species">
        <div class="question-body">
            <div class="question-num" id="q-num">Question 1</div>
            <div class="question-text" id="q-text"></div>
            <div class="question-hint" id="q-hint" style="display:none;"></div>
            <div class="options" id="q-options"></div>
            <div class="feedback" id="q-feedback"></div>
            <button class="next-btn" id="next-btn" onclick="nextQuestion()">
                Next Question →
            </button>
        </div>
    </div>

    <!-- SCORE CARD -->
    <div class="score-card" id="score-card">
        <div class="score-emoji" id="score-emoji">🏆</div>
        <div class="score-title" id="score-title">Quiz Complete!</div>
        <div class="score-subtitle" id="score-subtitle"></div>
        <div class="score-big" id="score-big"></div>
        <div class="score-out" id="score-out"></div>
        <div class="breakdown">
            <div class="breakdown-item">
                <div class="breakdown-num" style="color:#22c55e" id="bd-correct">0</div>
                <div class="breakdown-lbl">Correct</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-num" style="color:#ff4d4d" id="bd-wrong">0</div>
                <div class="breakdown-lbl">Wrong</div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-num" style="color:#f59e0b" id="bd-pct">0%</div>
                <div class="breakdown-lbl">Accuracy</div>
            </div>
        </div>
        <div class="score-actions">
            <button class="score-btn primary" onclick="restartQuiz()">🔄 Play Again</button>
            <a href="index.php#explore" class="score-btn secondary">🐾 Explore Species</a>
        </div>
    </div>
</div>

<script>
    // Questions from PHP
    var questions = <?php echo json_encode($questions); ?>;
    var current   = 0;
    var score     = 0;
    var answered  = false;
    var total     = questions.length;

    function loadQuestion() {
        var q = questions[current];
        answered = false;

        // Image
        document.getElementById('q-image').src = q.image ? 'uploads/' + q.image : 'uploads/default.jpg';

        // Number
        document.getElementById('q-num').textContent = 'Question ' + (current + 1) + ' of ' + total;

        // Text
        document.getElementById('q-text').innerHTML = q.question;

        // Hint
        var hintEl = document.getElementById('q-hint');
        if (q.hint) {
            hintEl.textContent = q.hint;
            hintEl.style.display = 'block';
        } else {
            hintEl.style.display = 'none';
        }

        // Options
        var optionsEl = document.getElementById('q-options');
        optionsEl.innerHTML = '';
        q.options.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = opt;
            btn.onclick = function() { selectAnswer(opt, btn, q.correct, q.id); };
            optionsEl.appendChild(btn);
        });

        // Hide feedback and next
        document.getElementById('q-feedback').className = 'feedback';
        document.getElementById('q-feedback').textContent = '';
        document.getElementById('next-btn').style.display = 'none';

        // Progress
        var pct = (current / total) * 100;
        document.getElementById('prog-fill').style.width = pct + '%';
        document.getElementById('prog-text').textContent = 'Question ' + (current + 1) + ' of ' + total;
        document.getElementById('score-live').textContent = 'Score: ' + score;
    }

    function selectAnswer(selected, btn, correct, speciesId) {
        if (answered) return;
        answered = true;

        // Disable all buttons
        var allBtns = document.querySelectorAll('.option-btn');
        allBtns.forEach(function(b) {
            b.disabled = true;
            if (b.textContent === correct) b.classList.add('correct');
        });

        var feedback = document.getElementById('q-feedback');

        if (selected === correct) {
            score++;
            btn.classList.add('correct');
            feedback.textContent = '✅ Correct! Well done!';
            feedback.className = 'feedback correct';
        } else {
            btn.classList.add('wrong');
            feedback.textContent = '❌ Wrong! The correct answer is: ' + correct;
            feedback.className = 'feedback wrong';
        }

        document.getElementById('score-live').textContent = 'Score: ' + score;
        document.getElementById('next-btn').style.display = 'block';

        // If last question change button text
        if (current === total - 1) {
            document.getElementById('next-btn').textContent = 'See Results 🏆';
        }
    }

    function nextQuestion() {
        current++;
        if (current >= total) {
            showScore();
        } else {
            loadQuestion();
        }
    }

    function showScore() {
        document.getElementById('quiz-card').style.display = 'none';
        document.getElementById('score-card').style.display = 'block';
        document.querySelector('.progress-wrap').style.display = 'none';

        var pct = Math.round((score / total) * 100);

        // Emoji and message based on score
        var emoji, title, subtitle;
        if (pct === 100) {
            emoji = '🏆'; title = 'Perfect Score!'; subtitle = 'You\'re a true Wildlife Expert!';
        } else if (pct >= 80) {
            emoji = '🌟'; title = 'Excellent!'; subtitle = 'You really know your wildlife!';
        } else if (pct >= 60) {
            emoji = '👍'; title = 'Good Job!'; subtitle = 'Keep exploring to learn more!';
        } else if (pct >= 40) {
            emoji = '🌱'; title = 'Keep Learning!'; subtitle = 'Explore more species to improve!';
        } else {
            emoji = '🐾'; title = 'Keep Trying!'; subtitle = 'Every expert started as a beginner!';
        }

        document.getElementById('score-emoji').textContent = emoji;
        document.getElementById('score-title').textContent = title;
        document.getElementById('score-subtitle').textContent = subtitle;
        document.getElementById('score-big').textContent = score;
        document.getElementById('score-out').textContent = 'out of ' + total + ' questions';
        document.getElementById('bd-correct').textContent = score;
        document.getElementById('bd-wrong').textContent = total - score;
        document.getElementById('bd-pct').textContent = pct + '%';

        // Progress to 100%
        document.getElementById('prog-fill').style.width = '100%';
    }

    function restartQuiz() {
        // Shuffle questions
        questions.sort(function() { return Math.random() - 0.5; });
        current = 0;
        score   = 0;
        document.getElementById('quiz-card').style.display = 'block';
        document.getElementById('score-card').style.display = 'none';
        document.querySelector('.progress-wrap').style.display = 'block';
        loadQuestion();
    }

    // Start quiz
    loadQuestion();
</script>

</body>
</html>