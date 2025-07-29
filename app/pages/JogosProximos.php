<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmissão Ao Vivo - Copa das Panelas</title>
    <link rel="stylesheet" href="../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../public/css/header_geral.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(#281c3e, #0f051d);
            min-height: 100vh;
            color: #E0E0E0;
            line-height: 1.6;
            position: relative;
        }

        /* Garantir que o conteúdo tenha altura mínima */
        html, body {
            height: auto;
            min-height: 100vh;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            padding-bottom: 40px; /* Espaço normal para o footer */
        }

        /* Layout natural sem sobreposição */
        main.main-container {
            display: block;
            position: static;
            margin-bottom: 0;
        }

        /* Footer em fluxo normal */
        footer {
            display: block;
            position: static;
            margin-top: 0;
            clear: both;
        }

        .live-section {
            background-color: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            text-align: center;
        }

        .live-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .live-indicator {
            background: linear-gradient(45deg, #ff0000, #ff4444);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .live-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .video-placeholder {
            aspect-ratio: 16/9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2A2A2A, #1A1A1A);
            color: #95a5a6;
        }

        .video-placeholder i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .video-placeholder h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .video-placeholder p {
            opacity: 0.8;
            text-align: center;
            max-width: 400px;
        }

        .youtube-player {
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
        }

        /* Player Limpo - Sem Controles */
        .clean-player {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .clean-player iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .player-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 10;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .video-container:hover .player-overlay {
            opacity: 1;
        }

        .fullscreen-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.95);
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .fullscreen-btn i {
            display: block;
        }

        /* Tela Cheia Personalizada */
        .custom-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: black;
            z-index: 9999;
            display: none;
        }

        .custom-fullscreen iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .exit-fullscreen {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            background: rgba(255, 0, 0, 0.9) !important;
            color: white !important;
            border: 2px solid white !important;
            border-radius: 8px !important;
            padding: 15px 20px !important;
            cursor: pointer !important;
            font-size: 1.3rem !important;
            font-weight: bold !important;
            z-index: 99999 !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(10px) !important;
            pointer-events: auto !important;
            display: block !important;
        }

        .exit-fullscreen:hover {
            background: rgba(255, 0, 0, 1) !important;
            transform: scale(1.2) !important;
            box-shadow: 0 6px 20px rgba(255, 0, 0, 0.7) !important;
        }

        /* Botão de Iniciar Transmissão */
        .start-stream-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 12px;
        }

        .start-stream-btn {
            background: linear-gradient(45deg, #7B1FA2, #E1BEE7);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 20px 40px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(123, 31, 162, 0.4);
        }

        .start-stream-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(123, 31, 162, 0.6);
        }

        .start-stream-btn i {
            margin-right: 10px;
            font-size: 1.3rem;
        }

        /* CORREÇÃO: Sobrescrever CSS problemático do footer */
        html, body {
            height: auto !important;
            min-height: 100vh !important;
        }

        body {
            display: block !important; /* Sobrescrever flex do footer.css */
            flex-direction: initial !important;
            padding-bottom: 0;
        }

        main {
            flex: initial !important; /* Remover flex: 1 do footer.css */
            display: block !important;
        }

        /* Garantir que footer apareça após o conteúdo */
        footer {
            display: block !important;
            position: static !important;
            margin-top: 20px;
            width: 100%;
            clear: both;
        }

        /* Garantir altura mínima da página */
        .page-content {
            min-height: calc(100vh - 200px); /* Altura da viewport menos espaço para header/footer */
        }

        /* Sistema de Chat e Votação */
        .interaction-section {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 40px;
        }

        .chat-container {
            background: rgba(30, 30, 30, 0.9);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(123, 31, 162, 0.3);
        }

        .chat-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #E1BEE7;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }

        .chat-messages {
            height: 300px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-message {
            margin-bottom: 12px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 3px solid #7B1FA2;
        }

        .chat-message .username {
            font-weight: bold;
            color: #E1BEE7;
            font-size: 0.9rem;
        }

        .chat-message .message {
            color: #E0E0E0;
            margin-top: 3px;
        }

        .chat-message .time {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            float: right;
        }

        .chat-input-container {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px;
            border: 2px solid rgba(123, 31, 162, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .chat-input:focus {
            outline: none;
            border-color: #7B1FA2;
            background: rgba(255, 255, 255, 0.15);
        }

        .chat-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .chat-send-btn {
            padding: 12px 20px;
            background: #7B1FA2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .chat-send-btn:hover {
            background: #9C27B0;
            transform: translateY(-2px);
        }

        .voting-container {
            background: rgba(30, 30, 30, 0.9);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(123, 31, 162, 0.3);
        }

        .voting-section {
            margin-bottom: 30px;
        }

        .voting-section h4 {
            color: #E1BEE7;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vote-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .vote-option:hover {
            background: rgba(123, 31, 162, 0.2);
            border-color: rgba(123, 31, 162, 0.5);
        }

        .vote-option.selected {
            background: rgba(123, 31, 162, 0.3);
            border-color: #7B1FA2;
        }

        .vote-option-text {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #E0E0E0;
        }

        .vote-percentage {
            background: rgba(123, 31, 162, 0.6);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .vote-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .vote-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #7B1FA2, #E1BEE7);
            border-radius: 2px;
            transition: width 0.5s ease;
        }

        @media (max-width: 1024px) {
            .interaction-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .voting-container {
                order: -1;
            }
        }

        .media-player-container {
            aspect-ratio: 16/9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2A2A2A, #1A1A1A);
            color: white;
            text-align: center;
            padding: 40px;
        }

        .music-visualizer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .music-visualizer h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #E1BEE7;
        }

        .music-visualizer p {
            color: #95a5a6;
            font-size: 1rem;
        }

        .image-display {
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, #2A2A2A, #1A1A1A);
            border-radius: 12px;
            overflow: hidden;
        }

        .admin-controls {
            background-color: #2A2A2A;
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }

        .admin-title {
            color: #E1BEE7;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #E0E0E0;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background-color: #1E1E1E;
            border: 2px solid #444;
            border-radius: 8px;
            color: #E0E0E0;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #7B1FA2;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #7B1FA2;
            color: white;
        }

        .btn-primary:hover {
            background-color: #9C27B0;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #F44336;
            color: white;
        }

        .btn-danger:hover {
            background-color: #E53935;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #777;
            transform: translateY(-2px);
        }

        .live-info {
            background-color: #2A2A2A;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .live-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background-color: #1E1E1E;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #444;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #7B1FA2;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #95a5a6;
            margin-top: 5px;
        }

        .no-admin {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }

        .no-admin i {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .live-section {
                padding: 20px;
            }

            .live-title {
                font-size: 2rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .live-stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border: 1px solid #4CAF50;
            color: #66BB6A;
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid #F44336;
            color: #EF5350;
        }

        .alert-info {
            background-color: rgba(33, 150, 243, 0.2);
            border: 1px solid #2196F3;
            color: #64B5F6;
        }

        /* Seção de Próximos Jogos */
        .upcoming-games-section {
            background-color: #1E1E1E;
            border-left: 4px solid #7B1FA2;
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            text-align: center;
            position: relative;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .section-title::before {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #7B1FA2, transparent);
            border-radius: 2px;
        }

        .section-title i {
            color: #E1BEE7;
            font-size: 2rem;
            animation: iconBounce 2s ease-in-out infinite;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .game-card {
            background: linear-gradient(145deg, #2A2A2A, #1E1E1E);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            overflow: hidden;
            cursor: pointer;
        }

        .game-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(123, 31, 162, 0.1), rgba(156, 39, 176, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 20px;
        }

        .game-card:hover::before {
            opacity: 1;
        }

        .game-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: #7B1FA2;
            box-shadow:
                0 20px 40px rgba(123, 31, 162, 0.4),
                0 0 0 1px rgba(123, 31, 162, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .game-card.today {
            border-color: #FF9800;
            background: linear-gradient(145deg, #3A2A1A, #2A1F0A);
            animation: todayPulse 3s ease-in-out infinite;
        }

        @keyframes todayPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(255, 152, 0, 0);
            }
        }

        .game-badge {
            position: absolute;
            top: -8px;
            right: 20px;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .today-badge {
            background: linear-gradient(45deg, #FF6B35, #F7931E);
            animation: todayBadgePulse 2s ease-in-out infinite;
        }

        @keyframes todayBadgePulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(255, 107, 53, 0.6);
            }
        }

        .tomorrow-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }

        .game-date {
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(123, 31, 162, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(123, 31, 162, 0.2);
            position: relative;
            overflow: hidden;
        }

        .game-date::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .game-card:hover .game-date::before {
            left: 100%;
        }

        .game-date .date {
            font-size: 1.6rem;
            font-weight: 800;
            color: #E1BEE7;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 5px;
        }

        .game-date .time {
            font-size: 1.2rem;
            color: #B39DDB;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .game-date .time::before {
            content: '🕐';
            font-size: 1rem;
        }

        .game-teams {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            position: relative;
        }

        .team {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            flex: 1;
            transition: transform 0.3s ease;
        }

        .game-card:hover .team {
            transform: scale(1.05);
        }

        .team-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .game-card:hover .team-logo {
            border-color: #7B1FA2;
            box-shadow: 0 6px 20px rgba(123, 31, 162, 0.4);
            transform: rotate(5deg);
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(145deg, #1E1E1E, #2A2A2A);
            border: 4px solid #7B1FA2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #E1BEE7;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .game-card:hover .logo-placeholder {
            background: linear-gradient(145deg, #7B1FA2, #9C27B0);
            color: white;
            transform: rotate(-5deg);
            box-shadow: 0 6px 20px rgba(123, 31, 162, 0.4);
        }

        .team-name {
            font-weight: 700;
            color: white;
            text-align: center;
            font-size: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            transition: color 0.3s ease;
        }

        .game-card:hover .team-name {
            color: #E1BEE7;
        }

        .vs-divider {
            font-weight: 900;
            color: #7B1FA2;
            font-size: 1.5rem;
            margin: 0 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            position: relative;
            transition: all 0.3s ease;
        }

        .vs-divider::before,
        .vs-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #7B1FA2, transparent);
            transition: all 0.3s ease;
        }

        .vs-divider::before {
            left: -40px;
        }

        .vs-divider::after {
            right: -40px;
        }

        .game-card:hover .vs-divider {
            color: #E1BEE7;
            transform: scale(1.1);
        }

        .game-card:hover .vs-divider::before,
        .game-card:hover .vs-divider::after {
            background: linear-gradient(90deg, transparent, #E1BEE7, transparent);
            width: 40px;
        }

        .game-group {
            text-align: center;
            background: linear-gradient(45deg, rgba(123, 31, 162, 0.3), rgba(156, 39, 176, 0.3));
            color: #E1BEE7;
            padding: 10px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 700;
            border: 2px solid rgba(123, 31, 162, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .game-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .game-card:hover .game-group::before {
            left: 100%;
        }

        .game-card:hover .game-group {
            background: linear-gradient(45deg, rgba(123, 31, 162, 0.5), rgba(156, 39, 176, 0.5));
            border-color: #E1BEE7;
            transform: scale(1.05);
        }

        .no-games {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #95a5a6;
            background: linear-gradient(145deg, #2A2A2A, #1E1E1E);
            border-radius: 20px;
            border: 2px dashed rgba(149, 165, 166, 0.3);
        }

        .no-games i {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.7;
            animation: float 3s ease-in-out infinite;
        }

        .no-games h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: white;
            font-weight: 700;
        }

        .no-games p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* Responsividade melhorada */
        @media (max-width: 1200px) {
            .games-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .games-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .game-card {
                padding: 20px;
            }

            .game-teams {
                flex-direction: column;
                gap: 20px;
                padding: 15px;
            }

            .team {
                flex-direction: row;
                gap: 15px;
            }

            .team-logo,
            .logo-placeholder {
                width: 50px;
                height: 50px;
            }

            .vs-divider {
                margin: 15px 0;
                font-size: 1.3rem;
            }

            .vs-divider::before,
            .vs-divider::after {
                display: none;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .game-date .date {
                font-size: 1.4rem;
            }

            .game-date .time {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 20px 10px;
            }

            .upcoming-games-section {
                padding: 20px;
            }

            .section-title {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 10px;
            }

            .game-card {
                padding: 15px;
            }

            .team-name {
                font-size: 0.9rem;
            }
        }

        /* Animações adicionais */
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }

        .game-card {
            background-image: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.03),
                transparent
            );
            background-size: 200px 100%;
            background-repeat: no-repeat;
            animation: shimmer 3s infinite;
        }

        .game-card:hover {
            animation: none;
        }

        .card-hovered {
            filter: brightness(1.1) saturate(1.2);
        }

        /* Efeito de glow para cards de hoje */
        .game-card.today::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #FF6B35, #F7931E, #FF6B35);
            border-radius: 22px;
            z-index: -1;
            opacity: 0.7;
            filter: blur(8px);
            animation: glowRotate 3s linear infinite;
        }

        @keyframes glowRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Efeito de loading skeleton */
        .loading-skeleton {
            background: linear-gradient(90deg, #2A2A2A 25%, #3A3A3A 50%, #2A2A2A 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Efeito de ondas no hover */
        .game-card {
            position: relative;
            overflow: hidden;
        }

        .game-card::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(123, 31, 162, 0.3) 0%, transparent 70%);
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            pointer-events: none;
        }

        .game-card:hover::after {
            width: 300px;
            height: 300px;
        }

        /* Melhorias no texto */
        .team-name {
            position: relative;
            overflow: hidden;
        }

        .team-name::before {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(123, 31, 162, 0.8), transparent);
            color: white;
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .game-card:hover .team-name::before {
            transform: translateX(100%);
        }
    </style>
</head>
<body>
    <?php
        include 'header_geral.php';
        include '../config/conexao.php';

        $pdo = conectar();

        // Verificar se há transmissão ativa
        $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
        $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar se há mídia ativa
        $stmt = $pdo->query("SELECT * FROM active_media ORDER BY created_at DESC LIMIT 1");
        $activeMedia = $stmt->fetch(PDO::FETCH_ASSOC);

        $mediaFile = null;
        if ($activeMedia && $activeMedia['media_id']) {
            // Verificar se deve mostrar na página de transmissão
            $currentPage = 'JogosProximos';
            $shouldShow = true;

            if (!empty($activeMedia['target_pages'])) {
                $targetPages = json_decode($activeMedia['target_pages'], true);
                if (is_array($targetPages)) {
                    $shouldShow = in_array($currentPage, $targetPages);
                }
            }

            if ($shouldShow) {
                $stmt = $pdo->prepare("SELECT * FROM media_library WHERE id = ?");
                $stmt->execute([$activeMedia['media_id']]);
                $mediaFile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $activeMedia = null; // Não mostrar se não deve aparecer nesta página
            }
        }

        // Processar ações do admin
        $message = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['admin_id'])) {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'start_live':
                        $youtube_url = trim($_POST['youtube_url']);
                        $title = trim($_POST['title']);

                        if (!empty($youtube_url) && !empty($title)) {
                            // Extrair ID do YouTube
                            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);

                            if (isset($matches[1])) {
                                $youtube_id = $matches[1];

                                // Desativar streams anteriores
                                $pdo->query("UPDATE live_streams SET status = 'inativo'");

                                // Inserir nova stream
                                $stmt = $pdo->prepare("INSERT INTO live_streams (title, youtube_id, youtube_url, status, admin_id, created_at) VALUES (?, ?, ?, 'ativo', ?, NOW())");
                                $stmt->execute([$title, $youtube_id, $youtube_url, $_SESSION['admin_id']]);

                                $message = 'Transmissão iniciada com sucesso!';
                                $messageType = 'success';

                                // Recarregar dados
                                $stmt = $pdo->query("SELECT * FROM live_streams WHERE status = 'ativo' ORDER BY created_at DESC LIMIT 1");
                                $liveStream = $stmt->fetch(PDO::FETCH_ASSOC);
                            } else {
                                $message = 'URL do YouTube inválida!';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Preencha todos os campos!';
                            $messageType = 'error';
                        }
                        break;

                    case 'stop_live':
                        $pdo->query("UPDATE live_streams SET status = 'inativo'");
                        $message = 'Transmissão encerrada!';
                        $messageType = 'info';
                        $liveStream = null;
                        break;
                }
            }
        }
    ?>

    <main class="main-container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="live-section">
            <?php if ($liveStream): ?>
                <div class="live-header">
                    <div class="live-indicator">
                        <i class="fas fa-circle"></i> AO VIVO
                    </div>
                    <h1 class="live-title"><?= htmlspecialchars($liveStream['title']) ?></h1>
                </div>

                <div class="video-container" id="livePlayer">
                    <iframe id="liveIframe"
                            class="clean-player"
                            src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($liveStream['youtube_id']) ?>?autoplay=1&mute=0&controls=0&disablekb=1&fs=0&modestbranding=1&rel=0&iv_load_policy=3&cc_load_policy=0&playsinline=1&enablejsapi=0&showinfo=0&start=0"
                            title="Transmissão ao vivo"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                            allowfullscreen>
                    </iframe>

                    <!-- Overlay para iniciar transmissão se autoplay falhar -->
                    <div class="start-stream-overlay" id="startStreamOverlay">
                        <button class="start-stream-btn" onclick="startStream()">
                            <i class="fas fa-play"></i>
                            Iniciar Transmissão
                        </button>
                    </div>

                    <div class="player-overlay" onclick="toggleFullscreen()">
                        <button class="fullscreen-btn" title="Expandir para Tela Cheia">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>

                <div class="live-info">
                    <h3><i class="fas fa-info-circle"></i> Informações da Transmissão</h3>
                    <p><strong>Iniciada em:</strong> <?= date('d/m/Y H:i', strtotime($liveStream['created_at'])) ?></p>
                    <p><strong>Status:</strong> <span style="color: #66BB6A;">Transmitindo ao vivo</span></p>
                </div>

                <!-- Sistema de Chat e Votação -->
                <div class="interaction-section">
                    <!-- Chat -->
                    <div class="chat-container">
                        <div class="chat-header">
                            <i class="fas fa-comments"></i>
                            <h3>Chat da Transmissão</h3>
                            <span id="onlineCount" style="background: rgba(123, 31, 162, 0.6); padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">0 online</span>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <div class="chat-message">
                                <div class="username">Sistema</div>
                                <div class="message">Bem-vindos ao chat da transmissão! 🎉</div>
                                <div class="time">agora</div>
                            </div>
                        </div>

                        <div class="chat-input-container">
                            <input type="text" id="chatInput" class="chat-input" placeholder="Digite sua mensagem..." maxlength="200">
                            <button onclick="sendMessage()" class="chat-send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>

                        <div style="margin-top: 10px; text-align: center;">
                            <input type="text" id="usernameInput" placeholder="Seu nome" style="padding: 8px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; width: 150px;">
                        </div>
                    </div>

                    <!-- Votação -->
                    <div class="voting-container">
                        <!-- Votação de Time -->
                        <div class="voting-section">
                            <h4><i class="fas fa-heart"></i> Qual time você torce?</h4>
                            <div class="vote-option" onclick="vote('team', 'time_a')">
                                <div class="vote-option-text">
                                    <i class="fas fa-futbol"></i>
                                    <span>Time A</span>
                                </div>
                                <span class="vote-percentage" id="team_a_percent">0%</span>
                            </div>
                            <div class="vote-bar">
                                <div class="vote-bar-fill" id="team_a_bar" style="width: 0%"></div>
                            </div>

                            <div class="vote-option" onclick="vote('team', 'time_b')">
                                <div class="vote-option-text">
                                    <i class="fas fa-futbol"></i>
                                    <span>Time B</span>
                                </div>
                                <span class="vote-percentage" id="team_b_percent">0%</span>
                            </div>
                            <div class="vote-bar">
                                <div class="vote-bar-fill" id="team_b_bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Votação de Previsão -->
                        <div class="voting-section">
                            <h4><i class="fas fa-trophy"></i> Quem vai ganhar?</h4>
                            <div class="vote-option" onclick="vote('prediction', 'win_a')">
                                <div class="vote-option-text">
                                    <i class="fas fa-crown"></i>
                                    <span>Time A vence</span>
                                </div>
                                <span class="vote-percentage" id="win_a_percent">0%</span>
                            </div>
                            <div class="vote-bar">
                                <div class="vote-bar-fill" id="win_a_bar" style="width: 0%"></div>
                            </div>

                            <div class="vote-option" onclick="vote('prediction', 'empate')">
                                <div class="vote-option-text">
                                    <i class="fas fa-handshake"></i>
                                    <span>Empate</span>
                                </div>
                                <span class="vote-percentage" id="empate_percent">0%</span>
                            </div>
                            <div class="vote-bar">
                                <div class="vote-bar-fill" id="empate_bar" style="width: 0%"></div>
                            </div>

                            <div class="vote-option" onclick="vote('prediction', 'win_b')">
                                <div class="vote-option-text">
                                    <i class="fas fa-crown"></i>
                                    <span>Time B vence</span>
                                </div>
                                <span class="vote-percentage" id="win_b_percent">0%</span>
                            </div>
                            <div class="vote-bar">
                                <div class="vote-bar-fill" id="win_b_bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div style="text-align: center; margin-top: 20px; font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                            <i class="fas fa-users"></i> <span id="totalVotes">0</span> votos registrados
                        </div>
                    </div>
                </div>
            <?php elseif ($activeMedia): ?>
                <?php if ($activeMedia['media_type'] === 'music' && !$activeMedia['show_controls']): ?>
                    <!-- Música sem controles - deixar para o sistema global -->
                    <div class="live-header">
                        <h1 class="live-title">Transmissão ao Vivo</h1>
                    </div>

                    <div class="video-container">
                        <div class="video-placeholder">
                            <i class="fas fa-video-slash"></i>
                            <h3>Nenhuma transmissão ativa</h3>
                            <p>No momento não há nenhuma transmissão ao vivo. Fique atento às redes sociais para saber quando começará o próximo jogo!</p>
                            <p style="margin-top: 15px; color: #7B1FA2; font-size: 0.9rem;">
                                <i class="fas fa-music"></i> Música de fundo: <?= htmlspecialchars($activeMedia['title']) ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Mídia com controles visuais -->
                    <div class="live-header">
                        <div class="live-indicator" style="background: linear-gradient(45deg, #7B1FA2, #9C27B0);">
                            <i class="fas fa-music"></i> MÍDIA ATIVA
                        </div>
                        <h1 class="live-title"><?= htmlspecialchars($activeMedia['title']) ?></h1>
                    </div>

                    <div class="video-container">
                        <?php if ($activeMedia['media_type'] === 'music' && $mediaFile): ?>
                            <!-- Música com controles visuais -->
                            <div class="media-player-container">
                                <div class="music-visualizer">
                                    <i class="fas fa-music" style="font-size: 4rem; color: #7B1FA2; margin-bottom: 20px;"></i>
                                    <h3>Reproduzindo Música</h3>
                                    <p><?= htmlspecialchars($mediaFile['file_name']) ?></p>
                                </div>
                                <audio controls autoplay style="width: 100%; margin-top: 20px;">
                                    <source src="../../<?= htmlspecialchars($mediaFile['file_path']) ?>" type="audio/mpeg">
                                    Seu navegador não suporta o elemento de áudio.
                                </audio>
                            </div>
                        <?php elseif ($activeMedia['media_type'] === 'video' && $mediaFile): ?>
                            <video controls autoplay style="width: 100%; height: 100%;">
                                <source src="../../<?= htmlspecialchars($mediaFile['file_path']) ?>" type="video/mp4">
                                Seu navegador não suporta o elemento de vídeo.
                            </video>
                        <?php elseif ($activeMedia['media_type'] === 'image' && $mediaFile): ?>
                            <div class="image-display" style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                <img src="../../<?= htmlspecialchars($mediaFile['file_path']) ?>" alt="Imagem ativa" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 12px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="live-info">
                        <h3><i class="fas fa-info-circle"></i> Informações da Mídia</h3>
                        <p><strong>Tipo:</strong> <?= ucfirst($activeMedia['media_type']) ?></p>
                        <p><strong>Iniciada em:</strong> <?= date('d/m/Y H:i', strtotime($activeMedia['created_at'])) ?></p>
                        <?php if ($mediaFile && !empty($mediaFile['description'])): ?>
                            <p><strong>Descrição:</strong> <?= htmlspecialchars($mediaFile['description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="live-header">
                    <h1 class="live-title">Transmissão ao Vivo</h1>
                </div>

                <div class="video-container">
                    <div class="video-placeholder">
                        <i class="fas fa-video-slash"></i>
                        <h3>Nenhuma transmissão ativa</h3>
                        <p>No momento não há nenhuma transmissão ao vivo. Fique atento às redes sociais para saber quando começará o próximo jogo!</p>
                    </div>
                </div>
            <?php endif; ?>


        </div>

        <!-- Seção de Próximos Jogos -->
        <div class="upcoming-games-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i> Próximos Jogos
            </h2>

            <div class="games-grid">
                <?php
                    // Buscar próximos jogos do calendário
                    require_once '../classes/TournamentManager.php';
                    $tournamentManager = new TournamentManager($pdo);
                    $tournament = $tournamentManager->getCurrentTournament();

                    if ($tournament) {
                        // Buscar próximos 6 jogos agendados
                        $stmt = $pdo->prepare("
                            SELECT m.*,
                                   t1.nome as team1_name, t1.logo as team1_logo,
                                   t2.nome as team2_name, t2.logo as team2_logo,
                                   g.nome as group_name
                            FROM matches m
                            LEFT JOIN times t1 ON m.team1_id = t1.id
                            LEFT JOIN times t2 ON m.team2_id = t2.id
                            LEFT JOIN grupos g ON m.group_id = g.id
                            WHERE m.tournament_id = ?
                            AND m.match_date >= NOW()
                            AND m.status = 'agendado'
                            ORDER BY m.match_date ASC
                            LIMIT 6
                        ");
                        $stmt->execute([$tournament['id']]);
                        $upcomingGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($upcomingGames)) {
                            foreach ($upcomingGames as $game) {
                                $matchDate = new DateTime($game['match_date']);
                                $now = new DateTime();
                                $isToday = $matchDate->format('Y-m-d') === $now->format('Y-m-d');
                                $isTomorrow = $matchDate->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d');

                                echo '<div class="game-card' . ($isToday ? ' today' : '') . '">';

                                if ($isToday) {
                                    echo '<div class="game-badge today-badge">HOJE</div>';
                                } elseif ($isTomorrow) {
                                    echo '<div class="game-badge tomorrow-badge">AMANHÃ</div>';
                                }

                                echo '<div class="game-date">';
                                echo '<div class="date">' . $matchDate->format('d/m') . '</div>';
                                echo '<div class="time">' . $matchDate->format('H:i') . '</div>';
                                echo '</div>';

                                echo '<div class="game-teams">';
                                echo '<div class="team">';
                                if (!empty($game['team1_logo'])) {
                                    echo '<img src="data:image/jpeg;base64,' . base64_encode($game['team1_logo']) . '" alt="Logo" class="team-logo">';
                                } else {
                                    echo '<div class="logo-placeholder"><i class="fas fa-shield-alt"></i></div>';
                                }
                                echo '<span class="team-name">' . htmlspecialchars($game['team1_name']) . '</span>';
                                echo '</div>';

                                echo '<div class="vs-divider">VS</div>';

                                echo '<div class="team">';
                                if (!empty($game['team2_logo'])) {
                                    echo '<img src="data:image/jpeg;base64,' . base64_encode($game['team2_logo']) . '" alt="Logo" class="team-logo">';
                                } else {
                                    echo '<div class="logo-placeholder"><i class="fas fa-shield-alt"></i></div>';
                                }
                                echo '<span class="team-name">' . htmlspecialchars($game['team2_name']) . '</span>';
                                echo '</div>';
                                echo '</div>';

                                if (!empty($game['group_name'])) {
                                    echo '<div class="game-group">' . htmlspecialchars($game['group_name']) . '</div>';
                                }

                                echo '</div>';
                            }
                        } else {
                            echo '<div class="no-games">';
                            echo '<i class="fas fa-calendar-times"></i>';
                            echo '<h3>Nenhum jogo agendado</h3>';
                            echo '<p>Não há jogos programados no momento.</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-games">';
                        echo '<i class="fas fa-trophy"></i>';
                        echo '<h3>Nenhum campeonato ativo</h3>';
                        echo '<p>Não há campeonato ativo no momento.</p>';
                        echo '</div>';
                    }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh da página a cada 30 segundos para verificar novas transmissões
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Animações de entrada dos cards
        document.addEventListener('DOMContentLoaded', function() {
            const gameCards = document.querySelectorAll('.game-card');

            // Adicionar classe inicial para animação
            gameCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(50px)';
                card.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';

                // Animar entrada com delay
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Efeito de parallax suave no scroll
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const parallax = document.querySelector('.upcoming-games-section');
                if (parallax) {
                    const speed = scrolled * 0.1;
                    parallax.style.transform = `translateY(${speed}px)`;
                }
            });

            // Adicionar efeito de hover com som (opcional)
            gameCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    // Adicionar classe para efeito extra
                    this.classList.add('card-hovered');
                });

                card.addEventListener('mouseleave', function() {
                    this.classList.remove('card-hovered');
                });

                // Efeito de clique
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Contador regressivo para jogos de hoje
            const todayCards = document.querySelectorAll('.game-card.today');
            todayCards.forEach(card => {
                const timeElement = card.querySelector('.time');
                if (timeElement) {
                    const gameTime = timeElement.textContent.trim();
                    // Aqui você pode adicionar lógica de countdown se necessário
                }
            });
        });

        // Adicionar efeito de partículas no hover (opcional)
        function createParticle(x, y) {
            const particle = document.createElement('div');
            particle.style.position = 'fixed';
            particle.style.left = x + 'px';
            particle.style.top = y + 'px';
            particle.style.width = '4px';
            particle.style.height = '4px';
            particle.style.background = '#7B1FA2';
            particle.style.borderRadius = '50%';
            particle.style.pointerEvents = 'none';
            particle.style.zIndex = '9999';
            particle.style.transition = 'all 1s ease-out';

            document.body.appendChild(particle);

            setTimeout(() => {
                particle.style.transform = `translate(${(Math.random() - 0.5) * 100}px, ${(Math.random() - 0.5) * 100}px)`;
                particle.style.opacity = '0';
            }, 10);

            setTimeout(() => {
                document.body.removeChild(particle);
            }, 1000);
        }

        // Controle específico para página de transmissão
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎵 Página de transmissão carregada');

            // Aguardar um pouco para garantir que o sistema global foi inicializado
            setTimeout(function() {
                <?php if ($activeMedia && isset($activeMedia['show_controls']) && $activeMedia['show_controls'] && $activeMedia['media_type'] !== 'youtube'): ?>
                    // Há mídia com controles visuais, pausar sistema global temporariamente
                    if (window.globalSyncAudio) {
                        console.log('🎵 Página de transmissão: Pausando sistema global (mídia com controles ativa)');
                        window.globalSyncAudio.pauseSystem();
                    }
                <?php else: ?>
                    // Não há mídia com controles, permitir sistema global funcionar
                    console.log('🎵 Página de transmissão: Sistema global ativo');
                    if (window.globalSyncAudio && window.globalSyncAudio.isPaused) {
                        window.globalSyncAudio.resumeSystem();
                    }
                <?php endif; ?>
            }, 500);
        });
    </script>

    <script>
        // Função para tela cheia personalizada - MOVER iframe em vez de duplicar
        function toggleFullscreen() {
            const customFullscreen = document.getElementById('customFullscreen');
            const originalIframe = document.getElementById('liveIframe');
            const videoContainer = document.querySelector('.video-container');

            if (originalIframe && customFullscreen) {
                // Salvar posição original
                if (!originalIframe.dataset.originalParent) {
                    originalIframe.dataset.originalParent = 'video-container';
                }

                // MOVER o iframe para a tela cheia (não duplicar)
                customFullscreen.appendChild(originalIframe);

                // Ajustar estilos para tela cheia
                originalIframe.style.width = '100vw';
                originalIframe.style.height = '100vh';
                originalIframe.style.position = 'absolute';
                originalIframe.style.top = '0';
                originalIframe.style.left = '0';
                originalIframe.style.zIndex = '99998';

                // Mostrar tela cheia
                customFullscreen.style.display = 'block';
                document.body.style.overflow = 'hidden';

                console.log('Tela cheia ativada - iframe movido (sem duplicação)');
            }
        }

        function exitFullscreen() {
            console.log('Saindo da tela cheia...');

            const customFullscreen = document.getElementById('customFullscreen');
            const originalIframe = document.getElementById('liveIframe');
            const videoContainer = document.querySelector('.video-container');

            if (customFullscreen && originalIframe && videoContainer) {
                // Esconder tela cheia
                customFullscreen.style.setProperty('display', 'none', 'important');

                // MOVER o iframe de volta para o container original
                videoContainer.appendChild(originalIframe);

                // Restaurar estilos originais do iframe
                originalIframe.style.width = '100%';
                originalIframe.style.height = '100%';
                originalIframe.style.position = 'absolute';
                originalIframe.style.top = '0';
                originalIframe.style.left = '0';
                originalIframe.style.zIndex = '1';

                // Recriar o overlay se não existir
                let overlay = videoContainer.querySelector('.player-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'player-overlay';
                    overlay.onclick = toggleFullscreen;
                    overlay.innerHTML = '<button class="fullscreen-btn" title="Expandir para Tela Cheia"><i class="fas fa-expand"></i></button>';
                    videoContainer.appendChild(overlay);
                }

                // Restaurar body
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';

                // Sair do fullscreen nativo se estiver ativo
                if (document.fullscreenElement) {
                    document.exitFullscreen().catch(err => console.log('Erro ao sair do fullscreen:', err));
                }

                console.log('Iframe movido de volta - sem duplicação de áudio');
                return true;
            }

            console.log('Elementos não encontrados para restaurar');
            return false;
        }

        // Múltiplas formas de sair da tela cheia
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                console.log('ESC pressionado');
                exitFullscreen();
            }
        });

        // Clique fora do vídeo para sair
        document.addEventListener('click', function(e) {
            const customFullscreen = document.getElementById('customFullscreen');
            const fullscreenIframe = document.getElementById('fullscreenIframe');

            if (customFullscreen && customFullscreen.style.display === 'block') {
                // Se clicou fora do iframe
                if (e.target === customFullscreen) {
                    console.log('Clicou fora do vídeo');
                    exitFullscreen();
                }
            }
        });

        // Duplo clique no vídeo para sair
        document.addEventListener('dblclick', function(e) {
            const customFullscreen = document.getElementById('customFullscreen');
            if (customFullscreen && customFullscreen.style.display === 'block') {
                console.log('Duplo clique detectado');
                exitFullscreen();
            }
        });

        // Prevenir todas as interações indesejadas no player
        const livePlayer = document.getElementById('livePlayer');
        if (livePlayer) {
            // Prevenir clique direito
            livePlayer.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            // Prevenir seleção de texto
            livePlayer.addEventListener('selectstart', function(e) {
                e.preventDefault();
            });

            // Prevenir arrastar
            livePlayer.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            // Prevenir teclas de atalho no player
            livePlayer.addEventListener('keydown', function(e) {
                // Bloquear espaço (pause), setas, etc.
                if ([32, 37, 38, 39, 40, 75, 77, 70].includes(e.keyCode)) {
                    e.preventDefault();
                }
            });
        }

        // Bloquear interação direta com iframe após carregamento
        setTimeout(() => {
            const iframe = document.getElementById('liveIframe');
            if (iframe) {
                iframe.style.pointerEvents = 'none';
            }
        }, 3000);

        // Sistema de Chat
        let chatMessages = [];
        let onlineUsers = Math.floor(Math.random() * 50) + 10; // Simular usuários online

        function updateOnlineCount() {
            document.getElementById('onlineCount').textContent = onlineUsers + ' online';
        }

        function addChatMessage(username, message, isSystem = false) {
            const chatContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';

            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                           now.getMinutes().toString().padStart(2, '0');

            messageDiv.innerHTML = `
                <div class="username" style="color: ${isSystem ? '#FFD700' : '#E1BEE7'}">${username}</div>
                <div class="message">${message}</div>
                <div class="time">${timeStr}</div>
            `;

            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Limitar mensagens (máximo 50)
            if (chatContainer.children.length > 50) {
                chatContainer.removeChild(chatContainer.firstChild);
            }
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const usernameInput = document.getElementById('usernameInput');
            const message = input.value.trim();
            const username = usernameInput.value.trim() || 'Anônimo';

            if (message) {
                addChatMessage(username, message);
                input.value = '';

                // Simular resposta ocasional
                if (Math.random() < 0.3) {
                    setTimeout(() => {
                        const responses = [
                            'Concordo!', 'Boa!', 'Vamos ver...', 'Show!',
                            'Que jogo!', 'Incrível!', 'Top demais!'
                        ];
                        const randomUser = 'Usuário' + Math.floor(Math.random() * 100);
                        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                        addChatMessage(randomUser, randomResponse);
                    }, 1000 + Math.random() * 3000);
                }
            }
        }

        // Enter para enviar mensagem
        document.getElementById('chatInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Sistema de Votação
        let votes = {
            team: { time_a: 0, time_b: 0 },
            prediction: { win_a: 0, empate: 0, win_b: 0 }
        };

        let userVotes = {
            team: null,
            prediction: null
        };

        function vote(category, option) {
            // Remover voto anterior se existir
            if (userVotes[category]) {
                votes[category][userVotes[category]]--;
            }

            // Adicionar novo voto
            votes[category][option]++;
            userVotes[category] = option;

            // Atualizar visual
            updateVoteDisplay(category);

            // Salvar no localStorage
            localStorage.setItem('userVotes', JSON.stringify(userVotes));
            localStorage.setItem('votes', JSON.stringify(votes));
        }

        function updateVoteDisplay(category) {
            const categoryVotes = votes[category];
            const total = Object.values(categoryVotes).reduce((a, b) => a + b, 0);

            // Remover seleção anterior
            document.querySelectorAll(`[onclick*="${category}"]`).forEach(el => {
                el.classList.remove('selected');
            });

            // Adicionar seleção atual
            if (userVotes[category]) {
                document.querySelector(`[onclick*="${userVotes[category]}"]`).classList.add('selected');
            }

            // Atualizar percentuais e barras
            Object.keys(categoryVotes).forEach(option => {
                const count = categoryVotes[option];
                const percentage = total > 0 ? Math.round((count / total) * 100) : 0;

                const percentElement = document.getElementById(option + '_percent');
                const barElement = document.getElementById(option + '_bar');

                if (percentElement) percentElement.textContent = percentage + '%';
                if (barElement) barElement.style.width = percentage + '%';
            });

            // Atualizar total de votos
            const totalVotes = Object.values(votes.team).reduce((a, b) => a + b, 0) +
                              Object.values(votes.prediction).reduce((a, b) => a + b, 0);
            document.getElementById('totalVotes').textContent = totalVotes;
        }

        // Carregar dados salvos
        function loadSavedData() {
            const savedVotes = localStorage.getItem('votes');
            const savedUserVotes = localStorage.getItem('userVotes');

            if (savedVotes) {
                votes = JSON.parse(savedVotes);
            }

            if (savedUserVotes) {
                userVotes = JSON.parse(savedUserVotes);
            }

            // Adicionar alguns votos iniciais se não houver dados
            if (!savedVotes) {
                votes.team.time_a = Math.floor(Math.random() * 20) + 5;
                votes.team.time_b = Math.floor(Math.random() * 20) + 5;
                votes.prediction.win_a = Math.floor(Math.random() * 15) + 3;
                votes.prediction.empate = Math.floor(Math.random() * 10) + 2;
                votes.prediction.win_b = Math.floor(Math.random() * 15) + 3;
            }

            updateVoteDisplay('team');
            updateVoteDisplay('prediction');
        }

        // Simular atividade no chat
        function simulateChatActivity() {
            const messages = [
                'Que jogo incrível!', 'Vamos Time A!', 'Time B vai ganhar!',
                'Que lance foi esse?', 'Goooool!', 'Quase!', 'Defesaça!',
                'Árbitro cego!', 'Que jogada!', 'Tá pegando fogo!'
            ];

            const usernames = [
                'TorcedorFiel', 'FutebolLover', 'CopaDasPanelas', 'Esportivo123',
                'TimeFan', 'JogoTop', 'Futebol2024', 'TorcidaUnida'
            ];

            setInterval(() => {
                if (Math.random() < 0.4) { // 40% chance a cada intervalo
                    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
                    const randomUser = usernames[Math.floor(Math.random() * usernames.length)] + Math.floor(Math.random() * 100);
                    addChatMessage(randomUser, randomMessage);
                }
            }, 8000 + Math.random() * 12000); // Entre 8-20 segundos
        }

        // Simular mudanças nos usuários online
        function simulateOnlineUsers() {
            setInterval(() => {
                const change = Math.floor(Math.random() * 6) - 3; // -3 a +3
                onlineUsers = Math.max(5, onlineUsers + change);
                updateOnlineCount();
            }, 15000); // A cada 15 segundos
        }

        // Função para iniciar transmissão manualmente
        function startStream() {
            const iframe = document.getElementById('liveIframe');
            const overlay = document.getElementById('startStreamOverlay');

            if (iframe && overlay) {
                // Recarregar iframe com autoplay garantido
                const currentSrc = iframe.src;
                const newSrc = currentSrc.replace('mute=0', 'mute=0').replace('autoplay=1', 'autoplay=1');

                iframe.src = newSrc;

                // Esconder overlay
                overlay.style.display = 'none';

                console.log('Transmissão iniciada manualmente');
            }
        }

        // Forçar autoplay da transmissão
        function forceAutoplay() {
            const iframe = document.getElementById('liveIframe');
            const overlay = document.getElementById('startStreamOverlay');

            if (iframe) {
                // Garantir que o vídeo comece automaticamente
                const currentSrc = iframe.src;

                // Se não tem autoplay=1, adicionar
                if (!currentSrc.includes('autoplay=1')) {
                    const separator = currentSrc.includes('?') ? '&' : '?';
                    iframe.src = currentSrc + separator + 'autoplay=1&mute=0';
                }

                console.log('Tentando autoplay automático...');

                // Se autoplay falhar, mostrar botão após 3 segundos
                setTimeout(() => {
                    // Verificar se o vídeo está tocando (método indireto)
                    // Se ainda não tocou, mostrar overlay
                    if (overlay && !iframe.src.includes('user_clicked=1')) {
                        overlay.style.display = 'flex';
                        console.log('Autoplay pode ter falhado - mostrando botão manual');
                    }
                }, 3000);
            }
        }

        // Detectar interação do usuário para ativar autoplay
        function enableAutoplayOnInteraction() {
            const iframe = document.getElementById('liveIframe');
            const overlay = document.getElementById('startStreamOverlay');

            if (iframe) {
                // Recarregar iframe com autoplay após primeira interação
                const currentSrc = iframe.src;
                const newSrc = currentSrc + '&user_clicked=1&timestamp=' + Date.now();
                iframe.src = newSrc;

                // Esconder overlay se estiver visível
                if (overlay) {
                    overlay.style.display = 'none';
                }

                console.log('Autoplay ativado após interação do usuário');
            }
        }

        // Adicionar listeners para primeira interação
        document.addEventListener('click', enableAutoplayOnInteraction, { once: true });
        document.addEventListener('keydown', enableAutoplayOnInteraction, { once: true });
        document.addEventListener('touchstart', enableAutoplayOnInteraction, { once: true });

        // Tentar ativar autoplay periodicamente
        function tryAutoplayPeriodically() {
            const iframe = document.getElementById('liveIframe');
            if (iframe && !iframe.src.includes('user_clicked=1')) {
                // Tentar recarregar iframe a cada 5 segundos até funcionar
                const interval = setInterval(() => {
                    if (iframe.src.includes('user_clicked=1')) {
                        clearInterval(interval);
                        return;
                    }

                    const currentSrc = iframe.src;
                    iframe.src = currentSrc + '&retry=' + Date.now();
                    console.log('Tentativa de autoplay...');
                }, 5000);

                // Parar tentativas após 30 segundos
                setTimeout(() => clearInterval(interval), 30000);
            }
        }

        // Inicializar sistemas
        updateOnlineCount();
        loadSavedData();
        simulateChatActivity();
        simulateOnlineUsers();

        // Forçar autoplay após carregamento
        setTimeout(forceAutoplay, 1000);

        // Tentar autoplay periodicamente se necessário
        setTimeout(tryAutoplayPeriodically, 5000);
    </script>

    <!-- Tela Cheia Personalizada -->
    <div id="customFullscreen" class="custom-fullscreen">
        <button class="exit-fullscreen" onclick="exitFullscreen(); return false;" onmousedown="exitFullscreen(); return false;" title="Sair da Tela Cheia">
            <i class="fas fa-times"></i> SAIR
        </button>
        <div style="position: absolute; top: 60px; right: 20px; color: white; font-size: 0.9rem; z-index: 99998;">
            ESC para sair
        </div>
        <!-- O iframe será movido para cá dinamicamente -->
    </div>

    </main> <!-- main-container -->

    <?php include 'footer.php'; ?>
</body>
</html>
