<?php

class UserDocumentsController {
    
    public function getUserDocuments($userId) {
        // Données simulées pour les documents
        $documents = [
            [
                "id" => 1,
                "name" => "Licence 2024",
                "filename" => "licence_2024.pdf",
                "file_size" => 1024000,
                "created_at" => "2024-01-15 10:30:00"
            ],
            [
                "id" => 2,
                "name" => "Certificat médical",
                "filename" => "certificat_medical.pdf",
                "file_size" => 512000,
                "created_at" => "2024-02-01 14:20:00"
            ],
            [
                "id" => 3,
                "name" => "Photo d'identité",
                "filename" => "photo_id.jpg",
                "file_size" => 256000,
                "created_at" => "2024-02-10 09:15:00"
            ]
        ];
        
        return [
            "success" => true,
            "documents" => $documents,
            "message" => "Documents simulés - API backend non accessible"
        ];
    }
    
    public function uploadDocument($userId, $documentData) {
        // Simulation d'upload
        return [
            "success" => true,
            "message" => "Document uploadé avec succès (simulation)",
            "document_id" => rand(100, 999)
        ];
    }
    
    public function deleteDocument($documentId) {
        // Simulation de suppression
        return [
            "success" => true,
            "message" => "Document supprimé avec succès (simulation)"
        ];
    }
    
    public function downloadDocument($documentId) {
        // Simulation de téléchargement
        return [
            "success" => true,
            "message" => "Téléchargement simulé",
            "download_url" => "/downloads/document_" . $documentId . ".pdf"
        ];
    }
}
?>