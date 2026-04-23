<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MobileTestController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if ($email === 'admin.com' && $password === 'password') {
            return response()->json([
                'success' => true,
                'message' => 'Handshake Successful! Welcome to Celina Engine.',
                'data' => [
                    'token' => 'test_token_' . bin2hex(random_bytes(10)),
                    'user' => [
                        'id' => 1,
                        'name' => 'Chakiii Admin',
                        'role' => 'Superuser',
                        'status' => 'Active'
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials. Check email or password.',
            'data' => null
        ], 401);
    }

    public function getNavigation()
    {
        // $sexyHtml = '
        // <!DOCTYPE html>
        // <html>
        // <head>
        //     <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        //     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
        //     <style>
        //         body { 
        //             font-family: "Poppins", sans-serif; 
        //             margin: 0; padding: 20px; 
        //             background-color: #F8F9FE; 
        //             color: #2D3436; 
        //         }
        //         .card {
        //             background: white;
        //             border-radius: 20px;
        //             padding: 25px;
        //             box-shadow: 0 10px 30px rgba(108, 92, 231, 0.1);
        //             text-align: center;
        //             margin-top: 20px;
        //             border: 1px solid rgba(108, 92, 231, 0.05);
        //         }
        //         .icon-box {
        //             width: 80px; height: 80px;
        //             background: linear-gradient(135deg, #6C5CE7, #a29bfe);
        //             border-radius: 50%;
        //             display: flex; align-items: center; justify-content: center;
        //             margin: 0 auto 20px;
        //             font-size: 40px;
        //             box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
        //         }
        //         h1 { font-size: 22px; margin-bottom: 10px; color: #1F1F1F; }
        //         p { font-size: 14px; color: #636E72; line-height: 1.6; }
        //         .badge {
        //             display: inline-block;
        //             padding: 6px 15px;
        //             background: #6C5CE7;
        //             color: white;
        //             border-radius: 50px;
        //             font-size: 12px;
        //             font-weight: 600;
        //             margin-top: 15px;
        //             text-transform: uppercase;
        //             letter-spacing: 1px;
        //         }
        //         .stats-grid {
        //             display: grid;
        //             grid-template-columns: 1fr 1fr;
        //             gap: 15px;
        //             margin-top: 25px;
        //         }
        //         .stat-item {
        //             background: #F1F2F6;
        //             padding: 15px;
        //             border-radius: 15px;
        //         }
        //         .stat-value { font-weight: 600; font-size: 18px; color: #6C5CE7; }
        //         .stat-label { font-size: 11px; color: #B2BEC3; text-transform: uppercase; }
        //     </style>
        // </head>
        // <body>
        //     <div class="card">
        //         <div class="icon-box">🚀</div>
        //         <h1>Engine Activated</h1>
        //         <p>You are now running <b>Hybrid Mode</b>. This page was rendered via raw HTML injected from Backend straight into Mobile Stack.</p>
        //         <div class="badge">System Nominal</div>
                
        //         <div class="stats-grid">
        //             <div class="stat-item">
        //                 <div class="stat-value">0ms</div>
        //                 <div class="stat-label">Deploy Delay</div>
        //             </div>
        //             <div class="stat-item">
        //                 <div class="stat-value">100%</div>
        //                 <div class="stat-label">Sexy Score</div>
        //             </div>
        //         </div>
        //     </div>
        // </body>
        // </html>';

        $gameHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
            <style>
                body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: #0f0c29; font-family: "Poppins", sans-serif; }
                canvas { display: block; background: radial-gradient(circle, #24243e, #0f0c29); }
                #ui { position: absolute; top: 50px; left: 0; width: 100%; text-align: center; color: #6C5CE7; pointer-events: none; }
                .score { font-size: 40px; text-shadow: 0 0 10px #6C5CE7; }
                .hint { font-size: 14px; color: #a29bfe; opacity: 0.8; }
                #msg { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center; display: none; }
                button { background: #6C5CE7; border: none; color: white; padding: 10px 20px; border-radius: 10px; font-family: "Poppins"; font-size: 18px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div id="ui">
                <div class="score" id="score">0</div>
                <div class="hint">Touch and Drag to Move</div>
            </div>
            <div id="msg">
                <h1>GAME OVER</h1>
                <button onclick="resetGame()">Try Again</button>
            </div>
            <canvas id="gameCanvas"></canvas>

            <script>
                const canvas = document.getElementById("gameCanvas");
                const ctx = canvas.getContext("2d");
                const scoreEl = document.getElementById("score");
                const msgEl = document.getElementById("msg");

                let score = 0;
                let gameActive = true;
                let player = { x: 0, y: 0, radius: 15, color: "#6C5CE7" };
                let enemies = [];
                
                function resize() {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                    player.x = canvas.width / 2;
                    player.y = canvas.height * 0.8;
                }

                window.addEventListener("resize", resize);
                resize();

                // Touch handling for your WebView
                canvas.addEventListener("touchmove", (e) => {
                    if(!gameActive) return;
                    const touch = e.touches[0];
                    player.x = touch.clientX;
                    player.y = touch.clientY;
                    e.preventDefault();
                }, { passive: false });

                function spawnEnemy() {
                    const radius = Math.random() * 20 + 10;
                    enemies.push({
                        x: Math.random() * canvas.width,
                        y: -radius,
                        radius: radius,
                        speed: Math.random() * 3 + 2 + (score / 10),
                        color: "#ff7675"
                    });
                }

                function update() {
                    if (!gameActive) return;
                    
                    if (Math.random() < 0.05) spawnEnemy();

                    enemies.forEach((enemy, index) => {
                        enemy.y += enemy.speed;

                        // Collision Detection
                        const dx = player.x - enemy.x;
                        const dy = player.y - enemy.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);

                        if (distance < player.radius + enemy.radius) {
                            gameActive = false;
                            msgEl.style.display = "block";
                        }

                        if (enemy.y > canvas.height + enemy.radius) {
                            enemies.splice(index, 1);
                            score++;
                            scoreEl.innerText = score;
                        }
                    });
                }

                function draw() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Draw Player with Glow
                    ctx.shadowBlur = 15;
                    ctx.shadowColor = player.color;
                    ctx.beginPath();
                    ctx.arc(player.x, player.y, player.radius, 0, Math.PI * 2);
                    ctx.fillStyle = player.color;
                    ctx.fill();
                    ctx.closePath();

                    // Draw Enemies
                    ctx.shadowColor = "#ff7675";
                    enemies.forEach(enemy => {
                        ctx.beginPath();
                        ctx.arc(enemy.x, enemy.y, enemy.radius, 0, Math.PI * 2);
                        ctx.fillStyle = enemy.color;
                        ctx.fill();
                        ctx.closePath();
                    });

                    ctx.shadowBlur = 0;
                    requestAnimationFrame(() => {
                        update();
                        draw();
                    });
                }

                function resetGame() {
                    score = 0;
                    scoreEl.innerText = "0";
                    enemies = [];
                    gameActive = true;
                    msgEl.style.display = "none";
                    resize();
                }

                draw();
            </script>
        </body>
        </html>';

        $countDownGame = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

        <style>
        :root {
            --bg: #F5F6FA;
            --purple: #6C5CE7;
            --purple-soft: #A29BFE;
            --text: #2D3436;
            --muted: #B2BEC3;
            --green: #55EFC4;
            --red: #FF7675;
        }

        /* Disable ugly tap highlight */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text);
            height: 100vh;
        }

        /* TITLE */
        h3 {
            letter-spacing: 2px;
            color: var(--purple-soft);
            margin-bottom: 5px;
        }

        /* TIMER */
        #timer {
            font-size: 20px;
            font-weight: 600;
            color: var(--red);
            margin-bottom: 20px;
        }

        /* BOARD */
        .board {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 420px;
            padding: 0 20px; /* IMPORTANT: horizontal padding */
            box-sizing: border-box;
            margin-bottom: 25px;
        }

        /* LETTER TILE */
        .letter-tile {
            flex: 1;
            aspect-ratio: 1 / 1.2;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 18px;
            color: white;

            background: linear-gradient(135deg, var(--purple), var(--purple-soft));

            box-shadow: 0 6px 0 #5649c9;
            transition: all 0.1s ease;
        }

        /* MOBILE PRESS FEEDBACK */
        .letter-tile:active {
            transform: translateY(3px);
            box-shadow: 0 2px 0 #5649c9;
        }

        /* USED TILE */
        .letter-tile.used {
            background: #EAEAF5;
            color: var(--muted);
            box-shadow: none;
            transform: scale(0.92);
        }

        /* CONTROLS CARD */
        .controls {
            width: 100%;
            max-width: 420px;
            padding: 0 20px;
            box-sizing: border-box;
            text-align: center;
        }

        /* INPUT */
        input {
            width: 100%;
            padding: 16px;
            border-radius: 20px;
            border: 2px solid var(--purple);
            text-align: center;
            font-size: 22px;
            font-weight: 600;
            background: white;
            outline: none;
            margin-bottom: 10px;
            text-transform: uppercase;
            caret-color: transparent;
        }

        /* BUTTON BASE */
        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 999px;
            border: none;
            font-weight: 600;
            font-family: "Poppins";
            font-size: 15px;
            margin-top: 10px;
            transition: all 0.12s ease;
        }

        /* PRIMARY */
        .btn-green {
            background: var(--green);
            color: white;
            box-shadow: 0 8px 20px rgba(85, 239, 196, 0.4);
        }

        /* SECONDARY (RESET) */
        .btn-red {
            background: var(--red);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 118, 117, 0.4);
        }

        /* PURPLE */
        .btn-purple {
            background: var(--purple);
            color: white;
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.4);
        }

        /* PRESS EFFECT */
        .btn:active {
            transform: scale(0.96);
            opacity: 0.9;
        }

        /* TEXT BUTTON REMOVED → now real button */
        .clear-btn {
            display: none;
        }

        /* STATES */
        #selectionControls,
        #gameplay,
        #endState {
            width: 100%;
        }

        .score-val {
            font-size: 42px;
            font-weight: 600;
            color: var(--purple);
        }

        .sexy-msg {
            margin-top: 10px;
            font-weight: 600;
            color: var(--green);
        }

        .checking {
            opacity: 0.6;
            pointer-events: none;
        }
        </style>
        </head>

        <body>

        <h3>COUNTDOWN</h3>
        <div id="timer">Ready?</div>

        <div class="board" id="letterBoard"></div>

        <div class="controls">

            <div id="selectionControls">
                <button class="btn btn-purple" onclick="generateLetters()">START GAME</button>
            </div>

            <div id="gameplay" style="display:none;">
                <input type="text" id="wordInput" placeholder="..." readonly />

                <!-- NEW RESET BUTTON -->
                <button class="btn btn-red" onclick="clearSelection()">RESET SELECTION</button>

                <button class="btn btn-green" id="submitBtn" onclick="submitWord()">SUBMIT WORD</button>
            </div>

            <div id="endState" style="display:none;">
                <div style="opacity:0.6; font-size:14px;">TOTAL SCORE</div>
                <div class="score-val" id="scoreDisplay">0</div>
                <div id="feedback" class="sexy-msg"></div>
                <button class="btn btn-purple" onclick="resetGame()">PLAY AGAIN</button>
            </div>

        </div>

        <script>
        const vowels = "AEIOU";
        const consonants = "BCDFGHJKLMNPQRSTVWXYZ";

        let currentLetters = [];
        let selectedIndices = [];
        let totalScore = 0;
        let timerInterval;

        function generateLetters() {
            document.getElementById("selectionControls").style.display = "none";

            currentLetters = [];
            selectedIndices = [];

            for(let i=0;i<4;i++) currentLetters.push(vowels[Math.random()*vowels.length|0]);
            for(let i=0;i<5;i++) currentLetters.push(consonants[Math.random()*consonants.length|0]);

            currentLetters.sort(()=>Math.random()-0.5);
            renderLetters();

            setTimeout(()=>{
                document.getElementById("timer").innerText = "00:30";
                document.getElementById("gameplay").style.display = "block";
                startTimer();
            },500);
        }

        function renderLetters(){
            const board = document.getElementById("letterBoard");
            board.innerHTML = "";

            currentLetters.forEach((l,i)=>{
                const btn = document.createElement("button");
                btn.className = "letter-tile";
                btn.innerText = l;
                btn.onclick = ()=>handleTileClick(i);
                board.appendChild(btn);
            });
        }

        function handleTileClick(index){
            const tile = document.querySelectorAll(".letter-tile")[index];
            const idx = selectedIndices.indexOf(index);

            if(idx>-1){
                selectedIndices.splice(idx,1);
                tile.classList.remove("used");
            }else{
                selectedIndices.push(index);
                tile.classList.add("used");
            }

            updateInput();
        }

        function updateInput(){
            document.getElementById("wordInput").value =
                selectedIndices.map(i=>currentLetters[i]).join("");
        }

        function clearSelection(){
            selectedIndices = [];
            document.querySelectorAll(".letter-tile").forEach(t=>t.classList.remove("used"));
            updateInput();
        }

        function startTimer(){
            let t=30;
            timerInterval = setInterval(()=>{
                t--;
                document.getElementById("timer").innerText =
                    "00:"+(t<10?"0"+t:t);
                if(t<=0) endGame("TIME UP!");
            },1000);
        }

        async function submitWord(){
            const val = document.getElementById("wordInput").value;
            if(val.length<3) return;

            const btn = document.getElementById("submitBtn");
            btn.classList.add("checking");
            document.getElementById("timer").innerText = "VERIFYING...";

            try{
                const res = await fetch(`https://api.dictionaryapi.dev/api/v2/entries/en/${val.toLowerCase()}`);
                if(res.ok){
                    totalScore += val.length;
                    document.getElementById("scoreDisplay").innerText = totalScore;
                    endGame(`"${val}" is valid! +${val.length}`);
                }else{
                    endGame(`"${val}" not a word`);
                }
            }catch(e){
                totalScore += val.length;
                document.getElementById("scoreDisplay").innerText = totalScore;
                endGame(`Offline +${val.length}`);
            }

            btn.classList.remove("checking");
        }

        function endGame(msg){
            clearInterval(timerInterval);
            document.getElementById("gameplay").style.display="none";
            document.getElementById("endState").style.display="block";
            document.getElementById("feedback").innerText=msg;
        }

        function resetGame(){
            document.getElementById("timer").innerText="Ready?";
            document.getElementById("selectionControls").style.display="block";
            document.getElementById("endState").style.display="none";
            document.getElementById("wordInput").value="";
            document.getElementById("letterBoard").innerHTML="";
            selectedIndices=[];
        }
        </script>

        </body>
        </html>
        ';

        return response()->json([
            [
                'label'      => 'Home',
                'icon'       => 'home',
                'route'      => 'home_screen',
                'icon_size'  => '24',
                'show_label' => 'false',
                'content_data' => '' 
            ],
            [
                'label'      => 'Chat',
                'icon'       => 'chat_bubble',
                'route'      => 'chat_screen',
                'icon_size'  => '24',
                'show_label' => 'false',
                'content_data' => ''
            ],
            [
                'label'      => 'Account',
                'icon'       => 'person',
                'route'      => 'account_screen',
                'icon_size'  => '24',
                'show_label' => 'false',
                'content_data' => ''
            ],
            [
                'label'      => 'Live',
                'icon'       => 'heart_smile',
                'route'      => 'custom_webview_screen',
                'icon_size'  => '24',
                'show_label' => 'false',
                'content_data' => $gameHtml 
            ],
            [
                'label'      => 'Countdown',
                'icon'       => 'edit_note',
                'route'      => 'custom_webview_screen',
                'icon_size'  => '24',
                'show_label' => 'false',
                'content_data' => $countDownGame 
            ],
        ]);
    }

    public function getHomeData(Request $request)
    {
        // In a real flow, you'd pull $request->user()->name
        $userName = "Admin"; 

        $menus = [
            ['label' => 'Projects', 'icon_id' => 'edit_note'],
            ['label' => 'Tasks', 'icon_id' => 'account_tree'],
            ['label' => 'Clients', 'icon_id' => 'people'],
            ['label' => 'Calendar', 'icon_id' => 'calendar'],
            ['label' => 'Documents', 'icon_id' => 'description'],
            ['label' => 'Invoices', 'icon_id' => 'assignment'],
            ['label' => 'Reports', 'icon_id' => 'insert_chart'],
            ['label' => 'Info', 'icon_id' => 'campaign'],
            ['label' => 'Notes', 'icon_id' => 'sticky_note'],
            ['label' => 'Files' , 'icon_id' => 'file_present'],
            ['label' => 'Team', 'icon_id' => 'group'],
            ['label' => 'Settings', 'icon_id' => 'settings'],
        ];

        $recentItems = [
            [
                'title' => 'Website Redesign',
                'status' => 'In Progress',
                'time' => 'Updated 2h ago',
                'icon_id' => 'desktop',
                'color' => '#FFF7E6',
                'text_color' => '#FFA940'
            ],
            [
                'title' => 'Marketing Plan',
                'status' => 'Active',
                'time' => 'Updated 5h ago',
                'icon_id' => 'campaign',
                'color' => '#F6FFED',
                'text_color' => '#52C41A'
            ],
            [
                'title' => 'Quarterly Report',
                'status' => 'Completed',
                'time' => 'Updated 1d ago',
                'icon_id' => 'article',
                'color' => '#E6F7FF',
                'text_color' => '#1890FF'
            ]
        ];

        return response()->json([
            'success' => true,
            'user_name' => $userName,
            'menus' => $menus,
            'recent_items' => $recentItems
        ]);
    }
}