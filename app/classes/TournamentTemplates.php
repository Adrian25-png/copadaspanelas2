<?php

class TournamentTemplates {
    
    public static function getTemplates() {
        return [
            'copa_mundo' => [
                'name' => 'Copa do Mundo',
                'description' => 'Formato da Copa do Mundo FIFA com 8 grupos de 4 times',
                'num_groups' => 8,
                'teams_per_group' => 4,
                'final_phase' => 'oitavas',
                'teams' => self::getWorldCupTeams()
            ],
            'brasileirao' => [
                'name' => 'Campeonato Brasileiro',
                'description' => 'Times do Campeonato Brasileiro divididos em grupos',
                'num_groups' => 4,
                'teams_per_group' => 5,
                'final_phase' => 'semifinais',
                'teams' => self::getBrazilianTeams()
            ],
            'champions_league' => [
                'name' => 'Champions League',
                'description' => 'Formato da UEFA Champions League',
                'num_groups' => 8,
                'teams_per_group' => 4,
                'final_phase' => 'oitavas',
                'teams' => self::getEuropeanTeams()
            ],
            'copa_america' => [
                'name' => 'Copa América',
                'description' => 'Seleções sul-americanas',
                'num_groups' => 3,
                'teams_per_group' => 4,
                'final_phase' => 'quartas',
                'teams' => self::getSouthAmericanTeams()
            ],
            'estadual' => [
                'name' => 'Campeonato Estadual',
                'description' => 'Times de um estado brasileiro',
                'num_groups' => 2,
                'teams_per_group' => 6,
                'final_phase' => 'semifinais',
                'teams' => self::getStateTeams()
            ],
            'escolar' => [
                'name' => 'Torneio Escolar',
                'description' => 'Formato para competições escolares',
                'num_groups' => 4,
                'teams_per_group' => 4,
                'final_phase' => 'semifinais',
                'teams' => self::getSchoolTeams()
            ]
        ];
    }
    
    public static function getTemplate($template_id) {
        $templates = self::getTemplates();
        return $templates[$template_id] ?? null;
    }
    
    private static function getWorldCupTeams() {
        return [
            // Grupo A
            ['name' => 'Brasil', 'group' => 0],
            ['name' => 'Argentina', 'group' => 0],
            ['name' => 'Uruguai', 'group' => 0],
            ['name' => 'Chile', 'group' => 0],
            
            // Grupo B
            ['name' => 'Alemanha', 'group' => 1],
            ['name' => 'França', 'group' => 1],
            ['name' => 'Espanha', 'group' => 1],
            ['name' => 'Itália', 'group' => 1],
            
            // Grupo C
            ['name' => 'Inglaterra', 'group' => 2],
            ['name' => 'Portugal', 'group' => 2],
            ['name' => 'Holanda', 'group' => 2],
            ['name' => 'Bélgica', 'group' => 2],
            
            // Grupo D
            ['name' => 'Croácia', 'group' => 3],
            ['name' => 'Dinamarca', 'group' => 3],
            ['name' => 'Suécia', 'group' => 3],
            ['name' => 'Suíça', 'group' => 3],
            
            // Grupo E
            ['name' => 'México', 'group' => 4],
            ['name' => 'Estados Unidos', 'group' => 4],
            ['name' => 'Canadá', 'group' => 4],
            ['name' => 'Costa Rica', 'group' => 4],
            
            // Grupo F
            ['name' => 'Japão', 'group' => 5],
            ['name' => 'Coreia do Sul', 'group' => 5],
            ['name' => 'Austrália', 'group' => 5],
            ['name' => 'Arábia Saudita', 'group' => 5],
            
            // Grupo G
            ['name' => 'Marrocos', 'group' => 6],
            ['name' => 'Senegal', 'group' => 6],
            ['name' => 'Nigéria', 'group' => 6],
            ['name' => 'Egito', 'group' => 6],
            
            // Grupo H
            ['name' => 'Polônia', 'group' => 7],
            ['name' => 'Ucrânia', 'group' => 7],
            ['name' => 'República Tcheca', 'group' => 7],
            ['name' => 'Sérvia', 'group' => 7]
        ];
    }
    
    private static function getBrazilianTeams() {
        return [
            // Grupo A
            ['name' => 'Flamengo', 'group' => 0],
            ['name' => 'Corinthians', 'group' => 0],
            ['name' => 'Palmeiras', 'group' => 0],
            ['name' => 'São Paulo', 'group' => 0],
            ['name' => 'Santos', 'group' => 0],
            
            // Grupo B
            ['name' => 'Fluminense', 'group' => 1],
            ['name' => 'Botafogo', 'group' => 1],
            ['name' => 'Vasco', 'group' => 1],
            ['name' => 'Atlético-MG', 'group' => 1],
            ['name' => 'Cruzeiro', 'group' => 1],
            
            // Grupo C
            ['name' => 'Grêmio', 'group' => 2],
            ['name' => 'Internacional', 'group' => 2],
            ['name' => 'Athletico-PR', 'group' => 2],
            ['name' => 'Coritiba', 'group' => 2],
            ['name' => 'Bahia', 'group' => 2],
            
            // Grupo D
            ['name' => 'Sport', 'group' => 3],
            ['name' => 'Ceará', 'group' => 3],
            ['name' => 'Fortaleza', 'group' => 3],
            ['name' => 'Goiás', 'group' => 3],
            ['name' => 'Atlético-GO', 'group' => 3]
        ];
    }
    
    private static function getEuropeanTeams() {
        return [
            // Grupo A
            ['name' => 'Real Madrid', 'group' => 0],
            ['name' => 'Barcelona', 'group' => 0],
            ['name' => 'Atlético Madrid', 'group' => 0],
            ['name' => 'Valencia', 'group' => 0],
            
            // Grupo B
            ['name' => 'Manchester United', 'group' => 1],
            ['name' => 'Manchester City', 'group' => 1],
            ['name' => 'Liverpool', 'group' => 1],
            ['name' => 'Chelsea', 'group' => 1],
            
            // Grupo C
            ['name' => 'Bayern Munich', 'group' => 2],
            ['name' => 'Borussia Dortmund', 'group' => 2],
            ['name' => 'RB Leipzig', 'group' => 2],
            ['name' => 'Bayer Leverkusen', 'group' => 2],
            
            // Grupo D
            ['name' => 'Juventus', 'group' => 3],
            ['name' => 'AC Milan', 'group' => 3],
            ['name' => 'Inter Milan', 'group' => 3],
            ['name' => 'Napoli', 'group' => 3],
            
            // Grupo E
            ['name' => 'PSG', 'group' => 4],
            ['name' => 'Olympique Marseille', 'group' => 4],
            ['name' => 'AS Monaco', 'group' => 4],
            ['name' => 'Lyon', 'group' => 4],
            
            // Grupo F
            ['name' => 'Ajax', 'group' => 5],
            ['name' => 'PSV', 'group' => 5],
            ['name' => 'Feyenoord', 'group' => 5],
            ['name' => 'AZ Alkmaar', 'group' => 5],
            
            // Grupo G
            ['name' => 'Benfica', 'group' => 6],
            ['name' => 'Porto', 'group' => 6],
            ['name' => 'Sporting', 'group' => 6],
            ['name' => 'Braga', 'group' => 6],
            
            // Grupo H
            ['name' => 'Celtic', 'group' => 7],
            ['name' => 'Rangers', 'group' => 7],
            ['name' => 'Shakhtar Donetsk', 'group' => 7],
            ['name' => 'Dynamo Kiev', 'group' => 7]
        ];
    }
    
    private static function getSouthAmericanTeams() {
        return [
            // Grupo A
            ['name' => 'Brasil', 'group' => 0],
            ['name' => 'Argentina', 'group' => 0],
            ['name' => 'Uruguai', 'group' => 0],
            ['name' => 'Chile', 'group' => 0],
            
            // Grupo B
            ['name' => 'Colômbia', 'group' => 1],
            ['name' => 'Peru', 'group' => 1],
            ['name' => 'Equador', 'group' => 1],
            ['name' => 'Venezuela', 'group' => 1],
            
            // Grupo C
            ['name' => 'Paraguai', 'group' => 2],
            ['name' => 'Bolívia', 'group' => 2],
            ['name' => 'Guiana', 'group' => 2],
            ['name' => 'Suriname', 'group' => 2]
        ];
    }
    
    private static function getStateTeams() {
        return [
            // Grupo A
            ['name' => 'Flamengo', 'group' => 0],
            ['name' => 'Fluminense', 'group' => 0],
            ['name' => 'Botafogo', 'group' => 0],
            ['name' => 'Vasco', 'group' => 0],
            ['name' => 'Bangu', 'group' => 0],
            ['name' => 'Madureira', 'group' => 0],
            
            // Grupo B
            ['name' => 'América-RJ', 'group' => 1],
            ['name' => 'Boavista', 'group' => 1],
            ['name' => 'Cabofriense', 'group' => 1],
            ['name' => 'Nova Iguaçu', 'group' => 1],
            ['name' => 'Portuguesa-RJ', 'group' => 1],
            ['name' => 'Resende', 'group' => 1]
        ];
    }
    
    private static function getSchoolTeams() {
        return [
            // Grupo A
            ['name' => 'Turma 9º A', 'group' => 0],
            ['name' => 'Turma 9º B', 'group' => 0],
            ['name' => 'Turma 9º C', 'group' => 0],
            ['name' => 'Turma 9º D', 'group' => 0],
            
            // Grupo B
            ['name' => 'Turma 8º A', 'group' => 1],
            ['name' => 'Turma 8º B', 'group' => 1],
            ['name' => 'Turma 8º C', 'group' => 1],
            ['name' => 'Turma 8º D', 'group' => 1],
            
            // Grupo C
            ['name' => 'Turma 7º A', 'group' => 2],
            ['name' => 'Turma 7º B', 'group' => 2],
            ['name' => 'Turma 7º C', 'group' => 2],
            ['name' => 'Turma 7º D', 'group' => 2],
            
            // Grupo D
            ['name' => 'Turma 6º A', 'group' => 3],
            ['name' => 'Turma 6º B', 'group' => 3],
            ['name' => 'Turma 6º C', 'group' => 3],
            ['name' => 'Turma 6º D', 'group' => 3]
        ];
    }
}
?>
