<?php
require_once __DIR__ . '/../config.php';

class TipificacionModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obtener todas las tipificaciones activas
     */
    public function getAllTipificaciones() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM tipificaciones_llamadas 
                WHERE activo = 1 
                ORDER BY nivel, parent_id, codigo
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tipificaciones: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener tipificaciones jerárquicas organizadas
     */
    public function getTipificacionesJerarquicas() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM tipificaciones_llamadas 
                WHERE activo = 1 
                ORDER BY nivel, parent_id, codigo
            ");
            $stmt->execute();
            $tipificaciones = $stmt->fetchAll();
            
            // Organizar en estructura jerárquica
            $jerarquia = [];
            foreach ($tipificaciones as $tip) {
                if ($tip['nivel'] == 1) {
                    // Nivel 1: Categorías principales
                    $jerarquia[$tip['id']] = [
                        'id' => $tip['id'],
                        'codigo' => $tip['codigo'],
                        'categoria' => $tip['categoria'],
                        'descripcion' => $tip['descripcion'],
                        'es_positivo' => $tip['es_positivo'],
                        'requiere_observacion' => $tip['requiere_observacion'],
                        'nivel' => $tip['nivel'],
                        'children' => []
                    ];
                } elseif ($tip['nivel'] == 2) {
                    // Nivel 2: Subcategorías
                    if (isset($jerarquia[$tip['parent_id']])) {
                        $jerarquia[$tip['parent_id']]['children'][$tip['id']] = [
                            'id' => $tip['id'],
                            'codigo' => $tip['codigo'],
                            'categoria' => $tip['categoria'],
                            'descripcion' => $tip['descripcion'],
                            'es_positivo' => $tip['es_positivo'],
                            'requiere_observacion' => $tip['requiere_observacion'],
                            'nivel' => $tip['nivel'],
                            'parent_id' => $tip['parent_id'],
                            'children' => []
                        ];
                    }
                } elseif ($tip['nivel'] == 3) {
                    // Nivel 3: Detalles
                    // Buscar el padre nivel 2
                    foreach ($jerarquia as $nivel1) {
                        if (isset($nivel1['children'][$tip['parent_id']])) {
                            $jerarquia[$nivel1['id']]['children'][$tip['parent_id']]['children'][$tip['id']] = [
                                'id' => $tip['id'],
                                'codigo' => $tip['codigo'],
                                'categoria' => $tip['categoria'],
                                'descripcion' => $tip['descripcion'],
                                'es_positivo' => $tip['es_positivo'],
                                'requiere_observacion' => $tip['requiere_observacion'],
                                'nivel' => $tip['nivel'],
                                'parent_id' => $tip['parent_id']
                            ];
                            break;
                        }
                    }
                }
            }
            
            return array_values($jerarquia);
        } catch (Exception $e) {
            throw new Exception("Error al obtener tipificaciones jerárquicas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener tipificaciones por categoría
     */
    public function getTipificacionesByCategoria($categoria) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM tipificaciones_llamadas 
                WHERE categoria = ? AND activo = 1 
                ORDER BY codigo
            ");
            $stmt->execute([$categoria]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tipificaciones por categoría: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener tipificación por ID
     */
    public function getTipificacionById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tipificaciones_llamadas WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error al obtener tipificación: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener categorías de tipificaciones
     */
    public function getCategorias() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT categoria 
                FROM tipificaciones_llamadas 
                WHERE activo = 1 
                ORDER BY categoria
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            throw new Exception("Error al obtener categorías: " . $e->getMessage());
        }
    }
}
?>
