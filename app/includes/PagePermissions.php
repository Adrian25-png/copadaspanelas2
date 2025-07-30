<?php
/**
 * Mapeamento de Permissões por Página
 * Sistema de Proteção Completo - Copa das Panelas
 */

class PagePermissions {
    
    /**
     * Mapeamento de páginas e suas permissões necessárias
     */
    private static $page_permissions = [
        // === TORNEIOS ===
        'tournament_list.php' => ['view_tournament'],
        'create_tournament.php' => ['create_tournament'],
        'edit_tournament.php' => ['edit_tournament'],
        'edit_tournament_simple.php' => ['edit_tournament'],
        'activate_tournament.php' => ['edit_tournament'],
        'tournament_templates.php' => ['view_tournament'],
        'tournament_wizard.php' => ['create_tournament'],
        'tournament_management.php' => ['edit_tournament'],
        'tournament_report.php' => ['view_tournament'],
        'tournament_standings.php' => ['view_tournament'],
        'check_tournaments.php' => ['view_tournament'],
        
        // === TIMES ===
        'all_teams.php' => ['view_team'],
        'team_manager.php' => ['create_team', 'edit_team'], // qualquer uma
        'editar_time.php' => ['edit_team'],
        'select_tournament_for_team.php' => ['view_team'],
        'select_tournament_for_team_management.php' => ['edit_team'],
        
        // === JOGOS ===
        'all_matches.php' => ['view_match'],
        'match_manager.php' => ['create_match', 'edit_match'], // qualquer uma
        'edit_match.php' => ['edit_match'],
        'edit_match_simple.php' => ['edit_match'],
        'edit_match_debug.php' => ['edit_match'],
        'bulk_edit_matches.php' => ['edit_match'],
        'bulk_results.php' => ['edit_results'],
        'match_details.php' => ['view_match'],
        'match_reports.php' => ['view_match'],
        'match_schedule.php' => ['view_match'],
        'global_calendar.php' => ['view_match'],
        
        // === JOGADORES ===
        'player_manager.php' => ['edit_team'], // jogadores são parte dos times
        'editar_jogador.php' => ['edit_team'],
        
        // === ADMINISTRADORES ===
        'admin_manager.php' => ['view_admin'],
        'create_admin.php' => ['create_admin'],
        'admin_permissions.php' => ['manage_permissions'],
        'setup_admin_tables.php' => ['manage_permissions'],
        
        // === SISTEMA ===
        'statistics.php' => ['view_statistics'],
        'system_health.php' => ['system_settings'],
        'system_logs.php' => ['view_logs'],
        'system_settings.php' => ['system_settings'],
        
        // === FASES FINAIS ===
        'finals_manager.php' => ['edit_match'],
        'finals_matches_manager.php' => ['edit_match'],
        'third_place_manager.php' => ['edit_match'],
        'knockout_generator.php' => ['create_match'],
        
        // === GRUPOS ===
        'group_manager.php' => ['edit_tournament'],
        
        // === TRANSMISSÃO ===
        'gerenciar_transmissao.php' => ['system_settings'],
        'template_configurator.php' => ['system_settings'],
        'template_preview.php' => ['view_tournament'],
        
        // === FERRAMENTAS DE DEBUG/TESTE (apenas super admins) ===
        'debug_basico.php' => ['system_settings'],
        'debug_exato.php' => ['system_settings'],
        'debug_third_place.php' => ['system_settings'],
        'debug_ultimos_jogos_sql.php' => ['system_settings'],
        'check_current_issue.php' => ['system_settings'],
        'check_database_tables.php' => ['system_settings'],
        'check_matches_table.php' => ['system_settings'],
        'check_status_column.php' => ['system_settings'],
        'create_tables.php' => ['system_settings'],
        'diagnose_classification_issue.php' => ['system_settings'],
        'fix_invalid_phases.php' => ['system_settings'],
        'fix_phase_column.php' => ['system_settings'],
        'fix_status_column.php' => ['system_settings'],
        'fix_status_final.php' => ['system_settings'],
        'futsal_corrections_summary.php' => ['system_settings'],
        'futsal_logic_analysis.php' => ['system_settings'],
        'implement_futsal_corrections.php' => ['system_settings'],
        'insert_finals_test_data.php' => ['system_settings'],
        'standings_corrected.php' => ['system_settings'],
        'sync_match_tables.php' => ['system_settings'],
        'update_phase_names.php' => ['system_settings'],
        'url_fix_summary.php' => ['system_settings'],
        
        // === TESTES (apenas super admins) ===
        'test_auto_progression.php' => ['system_settings'],
        'test_automatic_progression.php' => ['system_settings'],
        'test_button_removal.php' => ['system_settings'],
        'test_circulos_perfeitos.php' => ['system_settings'],
        'test_classificacao_automatica.php' => ['system_settings'],
        'test_cores_css.php' => ['system_settings'],
        'test_cores_forcadas.php' => ['system_settings'],
        'test_correcao_final.php' => ['system_settings'],
        'test_design_melhorado.php' => ['system_settings'],
        'test_finals_clean.php' => ['system_settings'],
        'test_finals_design.php' => ['system_settings'],
        'test_finals_manager.php' => ['system_settings'],
        'test_finals_manager_quick.php' => ['system_settings'],
        'test_html_output.php' => ['system_settings'],
        'test_links_finals.php' => ['system_settings'],
        'test_no_draws.php' => ['system_settings'],
        'test_nova_funcao.php' => ['system_settings'],
        'test_ordenacao_classificacao.php' => ['system_settings'],
        'test_phase_restrictions.php' => ['system_settings'],
        'test_progressao_eliminatorias.php' => ['system_settings'],
        'test_result_update.php' => ['system_settings'],
        'test_silent_automation.php' => ['system_settings'],
        'test_strict_validation.php' => ['system_settings'],
        'test_tamanho_reduzido.php' => ['system_settings'],
        'test_ultimos_jogos.php' => ['system_settings'],
        'create_test_third_place.php' => ['system_settings'],
        'tournament_example.php' => ['system_settings'],
        
        // === PÁGINAS ESPECIAIS (sem restrição ou com verificação própria) ===
        'dashboard_simple.php' => [], // sem restrição - todos podem acessar
        'login_simple.php' => [], // sem restrição
        'admin_header.php' => [], // componente
        'index.php' => [], // redirecionamento
        'admin_credentials.php' => [], // informativo
        'test_permissions.php' => [], // ferramenta de teste
        'demo_permissions.php' => [], // ferramenta de teste
        'demo_acesso_visual.php' => [], // ferramenta de teste
        'test_login_debug.php' => [], // ferramenta de teste
        'tournament_management_guide.php' => ['view_tournament'], // documentação
    ];
    
    /**
     * Obter permissões necessárias para uma página
     */
    public static function getRequiredPermissions($page_name) {
        return self::$page_permissions[$page_name] ?? null;
    }
    
    /**
     * Verificar se uma página requer permissões
     */
    public static function requiresPermissions($page_name) {
        $permissions = self::getRequiredPermissions($page_name);
        return $permissions !== null && !empty($permissions);
    }
    
    /**
     * Obter todas as páginas mapeadas
     */
    public static function getAllPages() {
        return array_keys(self::$page_permissions);
    }
    
    /**
     * Obter páginas por permissão
     */
    public static function getPagesByPermission($permission) {
        $pages = [];
        foreach (self::$page_permissions as $page => $perms) {
            if (in_array($permission, $perms)) {
                $pages[] = $page;
            }
        }
        return $pages;
    }
    
    /**
     * Verificar se usuário pode acessar uma página
     */
    public static function canAccessPage($page_name, $permissionManager) {
        $required_permissions = self::getRequiredPermissions($page_name);
        
        // Se não há mapeamento, negar acesso por segurança
        if ($required_permissions === null) {
            return false;
        }
        
        // Se não requer permissões, permitir acesso
        if (empty($required_permissions)) {
            return true;
        }
        
        // Verificar se tem qualquer uma das permissões necessárias
        return $permissionManager->hasAnyPermission($required_permissions);
    }
}
?>
