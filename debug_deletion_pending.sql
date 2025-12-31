-- Diagnostic des utilisateurs en attente de suppression

-- 1. Vérifier les utilisateurs avec status = 'pending_deletion'
SELECT 'Utilisateurs pending_deletion' as info, id, username, email, status, created_at, updated_at
FROM users 
WHERE status = 'pending_deletion';

-- 2. Vérifier les demandes de suppression validées
SELECT 'Demandes validées' as info, id, email, status, created_at, validated_at, expires_at, reason
FROM account_deletion_requests 
WHERE status = 'validated';

-- 3. Vérifier toutes les demandes de suppression
SELECT 'Toutes les demandes' as info, id, email, status, created_at, validated_at, expires_at, reason
FROM account_deletion_requests 
ORDER BY created_at DESC;

-- 4. Faire la jointure pour voir ce qui correspond
SELECT 
    'Jointure' as info,
    u.id, 
    u.username, 
    u.email, 
    u.status as user_status,
    adr.status as request_status,
    adr.validated_at,
    adr.reason,
    adr.created_at as request_created_at
FROM users u
LEFT JOIN account_deletion_requests adr ON u.email = adr.email
WHERE u.status = 'pending_deletion'
ORDER BY adr.validated_at DESC;
