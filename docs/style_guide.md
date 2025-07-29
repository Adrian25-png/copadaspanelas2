# Guia de Estilo - Copa das Panelas

## Padrão Visual Estabelecido

### 🎨 Paleta de Cores

**Cores Principais:**
- **Roxo Principal:** `#7B1FA2` - Usado para bordas, botões e destaques
- **Roxo Claro:** `#E1BEE7` - Usado para textos de destaque
- **Fundo Escuro:** `#1E1E1E` - Cor principal dos cards e elementos
- **Fundo Mais Escuro:** `#0f051d` - Background principal das páginas
- **Texto Principal:** `#E0E0E0` - Cor padrão do texto

**Cores de Status:**
- **Sucesso:** `#4CAF50` - Verde para jogos finalizados
- **Aviso:** `#FF9800` - Laranja para jogos em andamento
- **Erro:** `#F44336` - Vermelho para jogos cancelados
- **Info:** `#2196F3` - Azul para informações gerais

### 🏗️ Estrutura de Layout

**Container Principal:**
```css
.main {
    padding: 40px 20px 20px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
```

**Cards Padrão:**
```css
.standard-card {
    background-color: #1E1E1E;
    border-left: 4px solid #7B1FA2;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}
```

### 🔤 Tipografia

**Fonte Principal:** `Space Grotesk`
- Importar: `@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');`

**Hierarquia de Tamanhos:**
- Títulos principais: `2rem - 2.5rem`
- Subtítulos: `1.3rem - 1.5rem`
- Texto normal: `1rem`
- Texto pequeno: `0.9rem`

### 🎯 Princípios de Design

1. **Interface Limpa:** Remover títulos desnecessários e elementos redundantes
2. **Foco no Conteúdo:** Priorizar informações relevantes
3. **Consistência Visual:** Usar sempre os mesmos padrões de cores e espaçamento
4. **Responsividade:** Garantir funcionamento em todos os dispositivos

### 📱 Responsividade

**Breakpoints:**
- Mobile: `max-width: 768px`
- Tablet: `769px - 1024px`
- Desktop: `1025px+`

**Adaptações Mobile:**
- Padding reduzido: `20px 15px`
- Cards em coluna única
- Botões com tamanho touch-friendly
- Texto reduzido proporcionalmente

### 🧩 Componentes Padrão

**Botões:**
```css
.btn-standard {
    background-color: #1E1E1E;
    border: 2px solid #7B1FA2;
    color: #E1BEE7;
    padding: 12px 24px;
    border-radius: 8px;
}
```

**Estados Sem Conteúdo:**
```css
.no-tournament {
    text-align: center;
    padding: 80px 20px;
    color: white;
}
```

**Badges de Status:**
```css
.badge-standard {
    background-color: rgba(123, 31, 162, 0.2);
    border: 1px solid #7B1FA2;
    color: #E1BEE7;
    padding: 6px 12px;
    border-radius: 6px;
}
```

### 🔄 Animações

**Fade In Padrão:**
```css
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}
```

**Hover Effects:**
- Transformação sutil: `translateY(-2px)`
- Mudança de cor de fundo
- Transição suave: `transition: all 0.3s ease`

### 📋 Checklist de Padronização

**Para cada nova página:**
- [ ] Incluir `global_standards.css`
- [ ] Usar fonte `Space Grotesk`
- [ ] Aplicar background padrão
- [ ] Remover títulos desnecessários
- [ ] Usar cards com borda roxa
- [ ] Implementar estado "sem conteúdo"
- [ ] Garantir responsividade
- [ ] Aplicar animações fade-in
- [ ] Usar cores de status consistentes

### 🎨 Classes Utilitárias

**Espaçamento:**
- `.mb-10`, `.mb-20`, `.mb-30` - Margin bottom
- `.mt-10`, `.mt-20`, `.mt-30` - Margin top
- `.p-10`, `.p-20`, `.p-30` - Padding

**Alinhamento:**
- `.text-center` - Texto centralizado
- `.text-left` - Texto à esquerda
- `.text-right` - Texto à direita

**Visibilidade:**
- `.hidden` - Ocultar elemento
- `.visible` - Mostrar elemento

### 🚀 Implementação

**Ordem de CSS:**
1. `global_standards.css` (primeiro)
2. CSS específico da página
3. CSS do footer
4. CSS do header

**JavaScript:**
- Implementar fade-in automático
- Usar transições suaves
- Manter performance otimizada

### 📝 Notas Importantes

- **Sempre testar em mobile** antes de finalizar
- **Manter consistência** com o padrão estabelecido
- **Evitar elementos desnecessários** que poluam a interface
- **Priorizar acessibilidade** e usabilidade
- **Usar o sistema de torneio ativo** em todas as páginas públicas

### 🔧 Manutenção

**Atualizações futuras devem:**
- Seguir este guia rigorosamente
- Testar em todos os dispositivos
- Manter a performance otimizada
- Documentar mudanças significativas
