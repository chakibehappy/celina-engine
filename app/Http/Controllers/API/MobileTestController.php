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
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
            <style>
                body { font-family: "Poppins", sans-serif; background: #0f0c29; color: white; margin: 0; padding: 20px; text-align: center; overflow: hidden; }
                
                /* One-row board logic */
                .board { 
                    display: flex; 
                    justify-content: center; 
                    gap: 8px; 
                    margin: 25px 0; 
                    flex-wrap: nowrap; 
                    min-height: 65px; 
                }

                .letter-tile { 
                    width: 10vw; 
                    max-width: 45px;
                    height: 60px; 
                    background: linear-gradient(135deg, #6C5CE7, #a29bfe); 
                    border-radius: 8px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    font-size: 22px; 
                    font-weight: 600; 
                    box-shadow: 0 4px #4834d4;
                    transform: scale(0);
                    animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
                }

                @keyframes popIn {
                    to { transform: scale(1); }
                }

                .controls { margin-bottom: 30px; }
                
                button { 
                    background: #6C5CE7; border: none; padding: 12px 25px; border-radius: 50px; 
                    color: white; font-weight: 600; font-family: "Poppins"; cursor: pointer;
                    box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
                    transition: 0.3s;
                }

                button:active { transform: translateY(2px); box-shadow: none; }

                input { 
                    width: 85%; padding: 15px; border-radius: 12px; border: 2px solid #6C5CE7; 
                    background: #1e1e3f; color: white; font-family: "Poppins"; font-size: 18px; 
                    margin-bottom: 15px; outline: none; text-align: center;
                }

                .stats { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
                #timer { font-size: 22px; color: #ff7675; font-weight: 600; margin-bottom: 10px; }
                .sexy-msg { color: #55efc4; margin-top: 10px; font-weight: 600; min-height: 24px; }
            </style>
        </head>
        <body>
            <h3 style="letter-spacing: 2px; color: #a29bfe;">COUNTDOWN</h3>
            <div id="timer">Ready?</div>
            
            <div class="board" id="letterBoard">
                </div>

            <div class="controls" id="selectionControls">
                <button onclick="generateLetters()">SET LETTERS</button>
            </div>

            <div id="gameplay" style="display: none;">
                <input type="text" id="wordInput" placeholder="Enter longest word..." autocomplete="off" />
                <br>
                <button onclick="submitWord()" style="background: #00b894;">SUBMIT</button>
            </div>

            <div class="stats">
                <div style="font-size: 14px; opacity: 0.7;">TOTAL SCORE</div>
                <div style="font-size: 32px; font-weight: 600;" id="score">0</div>
                <div id="feedback" class="sexy-msg"></div>
                <button id="retryBtn" onclick="resetGame()" style="display: none; margin-top: 15px; background: #ff7675;">PLAY AGAIN</button>
            </div>

            <script>
                const vowels = "AEIOU";
                const consonants = "BCDFGHJKLMNPQRSTVWXYZ";
                let currentLetters = [];
                let score = 0;
                let timeLeft = 30;
                let timerInterval;

                function generateLetters() {
                    document.getElementById("selectionControls").style.display = "none";
                    currentLetters = [];
                    
                    // Generate a standard Countdown mix: 3 or 4 vowels, the rest consonants
                    const vowelCount = Math.floor(Math.random() * 2) + 3; 
                    
                    for(let i=0; i<9; i++) {
                        const isVowel = i < vowelCount;
                        const source = isVowel ? vowels : consonants;
                        currentLetters.push(source[Math.floor(Math.random() * source.length)]);
                    }

                    // Shuffle them so vowels arent all at the start
                    currentLetters.sort(() => Math.random() - 0.5);
                    
                    renderLetters();
                    
                    // Start gameplay after animation sequence (~1s)
                    setTimeout(() => {
                        document.getElementById("gameplay").style.display = "block";
                        startTimer();
                    }, 1200);
                }

                function renderLetters() {
                    const board = document.getElementById("letterBoard");
                    board.innerHTML = "";
                    currentLetters.forEach((l, index) => {
                        const div = document.createElement("div");
                        div.className = "letter-tile";
                        div.innerText = l;
                        // Staggered animation delay
                        div.style.animationDelay = `${index * 0.1}s`;
                        board.appendChild(div);
                    });
                }

                function startTimer() {
                    timeLeft = 30;
                    timerInterval = setInterval(() => {
                        timeLeft--;
                        document.getElementById("timer").innerText = `00:${timeLeft < 10 ? "0"+timeLeft : timeLeft}`;
                        if (timeLeft <= 0) endGame("TIME EXPIRED");
                    }, 1000);
                }

                function submitWord() {
                    const input = document.getElementById("wordInput").value.toUpperCase().trim();
                    if (!input) return;

                    let tempLetters = [...currentLetters];
                    let isValid = true;

                    for (let char of input) {
                        const index = tempLetters.indexOf(char);
                        if (index > -1) {
                            tempLetters.splice(index, 1);
                        } else {
                            isValid = false;
                            break;
                        }
                    }

                    if (isValid && input.length >= 2) {
                        score += input.length;
                        document.getElementById("score").innerText = score;
                        endGame(`EXCELLENT: ${input.length} PTS`);
                    } else {
                        endGame(input.length < 2 ? "TOO SHORT" : "INVALID LETTERS");
                    }
                }

                function endGame(msg) {
                    clearInterval(timerInterval);
                    document.getElementById("gameplay").style.display = "none";
                    document.getElementById("feedback").innerText = msg;
                    document.getElementById("retryBtn").style.display = "inline-block";
                }

                function resetGame() {
                    currentLetters = [];
                    document.getElementById("feedback").innerText = "";
                    document.getElementById("timer").innerText = "Ready?";
                    document.getElementById("selectionControls").style.display = "block";
                    document.getElementById("retryBtn").style.display = "none";
                    document.getElementById("wordInput").value = "";
                    document.getElementById("letterBoard").innerHTML = "";
                }
            </script>
        </body>
        </html>';

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