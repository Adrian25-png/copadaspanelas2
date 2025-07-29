/**
 * Sistema de áudio global com sincronização em tempo real
 */

class GlobalSyncAudio {
    constructor() {
        this.audio = null;
        this.syncInterval = null;
        this.positionInterval = null;
        this.currentPage = this.getCurrentPageName();
        this.currentMedia = null;
        this.isInitialized = false;
        this.isPaused = false; // Controle de pausa externa
        this.syncTolerance = 0.5; // Tolerância de sincronização em segundos
        this.lastSyncTime = 0;
        this.init();
    }

    getCurrentPageName() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        return filename.replace('.php', '');
    }

    init() {
        console.log(`🎵 Inicializando sistema de áudio sincronizado na página: ${this.currentPage}`);

        // Verificar sincronização global imediatamente
        this.checkGlobalSync();

        // Verificar sincronização a cada 3 segundos
        this.syncInterval = setInterval(() => {
            this.checkGlobalSync();
        }, 3000);

        // Monitorar posição do áudio a cada segundo
        this.positionInterval = setInterval(() => {
            this.monitorAudioPosition();
        }, 1000);

        // Escutar eventos de visibilidade da página
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !this.isPaused) {
                // Página ficou visível, re-sincronizar imediatamente
                console.log('👁️ Página visível, re-sincronizando...');
                setTimeout(() => this.checkGlobalSync(), 100);
            }
        });

        // Re-sincronizar quando a janela ganha foco
        window.addEventListener('focus', () => {
            if (!this.isPaused) {
                console.log('🎯 Janela em foco, re-sincronizando...');
                setTimeout(() => this.checkGlobalSync(), 100);
            }
        });
    }

    async checkGlobalSync() {
        // Se o sistema está pausado, não fazer nada
        if (this.isPaused) {
            return;
        }

        try {
            const response = await fetch('/copadaspanelas2/app/api/audio_sync.php');
            const data = await response.json();

            if (data.success && data.sync_data) {
                await this.handleSyncData(data.sync_data);
            } else {
                this.stopAudio();
            }
        } catch (error) {
            console.log('❌ Erro na sincronização:', error);
        }
    }

    async handleSyncData(syncData) {
        // Verificar se deve tocar na página atual
        if (!this.shouldPlayOnCurrentPage(syncData)) {
            this.stopAudio();
            return;
        }

        // Só processar música sem controles visuais
        if (syncData.media_type !== 'music' || syncData.show_controls) {
            this.stopAudio();
            return;
        }

        // Verificar se a mídia mudou
        const mediaChanged = !this.currentMedia ||
                           this.currentMedia.media_id !== syncData.media_id ||
                           this.currentMedia.file_path !== syncData.file_path;

        // Atualizar dados da mídia atual
        this.currentMedia = syncData;

        if (mediaChanged) {
            console.log('🎵 Nova mídia detectada:', syncData.title);
            await this.startSyncedAudio(syncData);
        } else {
            // Verificar se precisa sincronizar posição
            await this.syncAudioPosition(syncData);
        }
    }

    async startSyncedAudio(syncData) {
        if (!syncData.file_path) {
            console.log('❌ Caminho do arquivo não encontrado');
            return;
        }

        const audioSrc = `/copadaspanelas2/${syncData.file_path}`;

        // Se já está tocando a mesma música, apenas sincronizar posição
        if (this.audio && this.audio.src.includes(syncData.file_path) && !this.audio.paused) {
            console.log('🔄 Música já tocando, apenas sincronizando posição...');
            await this.syncAudioPosition(syncData);
            return;
        }

        // Parar áudio anterior apenas se for diferente
        if (this.audio && !this.audio.src.includes(syncData.file_path)) {
            console.log('🔄 Trocando para nova música...');
            this.stopAudio();
        }

        // Se não há áudio ou está pausado, criar/retomar
        if (!this.audio) {
            console.log('🎵 Criando novo elemento de áudio:', audioSrc);
            this.audio = new Audio(audioSrc);
            this.audio.loop = syncData.loop_enabled;
            this.audio.volume = 0.3;
            this.audio.preload = 'auto';

            // Aguardar carregamento
            try {
                await new Promise((resolve, reject) => {
                    this.audio.addEventListener('canplaythrough', resolve, { once: true });
                    this.audio.addEventListener('error', reject, { once: true });

                    // Timeout de 10 segundos
                    setTimeout(() => reject(new Error('Timeout no carregamento')), 10000);
                });
            } catch (error) {
                console.log('❌ Erro no carregamento:', error);
                return;
            }
        }

        // Calcular posição correta
        const targetPosition = syncData.current_position;

        if (targetPosition > 0 && targetPosition < syncData.duration) {
            this.audio.currentTime = targetPosition;
        }

        // Iniciar reprodução se não estiver tocando
        if (this.audio.paused) {
            try {
                await this.audio.play();
                console.log(`✅ Áudio sincronizado iniciado na posição ${targetPosition.toFixed(2)}s`);
                this.showSyncIndicator(syncData.title, targetPosition);
            } catch (error) {
                console.log('❌ Autoplay bloqueado:', error);
            }
        }
    }

    async syncAudioPosition(syncData) {
        if (!this.audio) {
            // Se não há áudio mas deveria ter, iniciar
            if (syncData.is_playing) {
                await this.startSyncedAudio(syncData);
            }
            return;
        }

        if (this.audio.paused && syncData.is_playing) {
            // Se áudio está pausado mas deveria estar tocando, retomar
            try {
                this.audio.currentTime = syncData.current_position;
                await this.audio.play();
                console.log('▶️ Áudio retomado na posição', syncData.current_position.toFixed(2));
            } catch (error) {
                console.log('❌ Erro ao retomar áudio:', error);
            }
            return;
        }

        if (!this.audio.paused) {
            const currentAudioTime = this.audio.currentTime;
            const expectedTime = syncData.current_position;
            const timeDifference = Math.abs(currentAudioTime - expectedTime);

            // Se a diferença for maior que a tolerância, sincronizar
            if (timeDifference > this.syncTolerance) {
                console.log(`🔄 Sincronizando posição: ${currentAudioTime.toFixed(2)}s → ${expectedTime.toFixed(2)}s (diff: ${timeDifference.toFixed(2)}s)`);

                // Ajustar posição
                this.audio.currentTime = expectedTime;

                // Se a diferença for muito grande, pode ser necessário pausar e reproduzir novamente
                if (timeDifference > 3.0) {
                    try {
                        await this.audio.play();
                    } catch (error) {
                        console.log('❌ Erro ao re-sincronizar:', error);
                    }
                }
            }
        }
    }

    monitorAudioPosition() {
        if (!this.audio || this.audio.paused || !this.currentMedia) {
            return;
        }

        // Verificar se o áudio ainda está tocando corretamente
        const now = Date.now();
        if (now - this.lastSyncTime > 10000) { // Re-sincronizar a cada 10 segundos
            this.checkGlobalSync();
            this.lastSyncTime = now;
        }

        // Verificar se chegou ao fim (para áudios sem loop)
        if (!this.currentMedia.loop_enabled &&
            this.audio.currentTime >= this.currentMedia.duration) {
            console.log('🔚 Áudio terminou');
            this.stopAudio();
        }
    }

    shouldPlayOnCurrentPage(syncData) {
        // Se target_pages é null ou vazio, tocar em todas as páginas
        if (!syncData.target_pages) {
            console.log(`✅ Deve tocar na página ${this.currentPage}: SIM (todas as páginas)`);
            return true;
        }

        try {
            const targetPages = JSON.parse(syncData.target_pages);
            const shouldPlay = targetPages.includes(this.currentPage);
            console.log(`${shouldPlay ? '✅' : '❌'} Deve tocar na página ${this.currentPage}: ${shouldPlay ? 'SIM' : 'NÃO'} (páginas alvo: ${targetPages.join(', ')})`);
            return shouldPlay;
        } catch (error) {
            console.log(`✅ Deve tocar na página ${this.currentPage}: SIM (erro no JSON, fallback)`);
            return true; // Se erro no JSON, tocar em todas
        }
    }

    stopAudio() {
        if (this.audio) {
            this.audio.pause();
            this.audio = null;
        }
        this.currentMedia = null;
        this.hideSyncIndicator();
        console.log('🔇 Sistema de áudio global parado');
    }

    // Método público para pausar temporariamente o sistema
    pauseSystem() {
        this.stopAudio();
        this.isPaused = true;
        console.log('⏸️ Sistema de áudio global pausado temporariamente');
    }

    // Método público para retomar o sistema
    resumeSystem() {
        this.isPaused = false;
        console.log('▶️ Sistema de áudio global retomado');
        // Verificar imediatamente se há mídia para tocar
        setTimeout(() => this.checkGlobalSync(), 100);
    }

    showSyncIndicator(title, position) {
        this.hideSyncIndicator();

        const indicator = document.createElement('div');
        indicator.id = 'copa-sync-indicator';
        indicator.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(45deg, #7B1FA2, #9C27B0);
                color: white;
                padding: 12px 16px;
                border-radius: 25px;
                font-size: 13px;
                font-weight: 600;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 6px 20px rgba(123, 31, 162, 0.4);
                animation: slideInSync 0.4s ease-out;
                max-width: 300px;
            ">
                <div style="
                    width: 10px;
                    height: 10px;
                    background: #4CAF50;
                    border-radius: 50%;
                    animation: syncPulse 1.5s infinite;
                "></div>
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <div>🎵 ${title}</div>
                    <div style="font-size: 11px; opacity: 0.8;">
                        Sincronizado • ${this.formatTime(position)}
                    </div>
                </div>
            </div>
            <style>
                @keyframes slideInSync {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes syncPulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.6; transform: scale(1.2); }
                }
            </style>
        `;

        document.body.appendChild(indicator);

        // Remover após 4 segundos
        setTimeout(() => {
            this.hideSyncIndicator();
        }, 4000);
    }

    hideSyncIndicator() {
        const indicator = document.getElementById('copa-sync-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    destroy() {
        console.log('🔄 Limpando sistema de áudio...');

        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        if (this.positionInterval) {
            clearInterval(this.positionInterval);
        }

        // Não parar o áudio ao trocar de página - deixar tocando
        // this.stopAudio();

        this.hideSyncIndicator();
    }

}

// Inicializar sistema de áudio sincronizado
document.addEventListener('DOMContentLoaded', function() {
    // Evitar múltiplas instâncias
    if (window.globalSyncAudio) {
        console.log('🎵 Sistema de áudio já inicializado, pulando...');
        return;
    }

    console.log('🎵 Carregando sistema de áudio sincronizado...');
    window.globalSyncAudio = new GlobalSyncAudio();
});

// Limpar ao sair da página
window.addEventListener('beforeunload', function() {
    if (window.globalSyncAudio) {
        window.globalSyncAudio.destroy();
    }
});
