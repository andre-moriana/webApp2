$content = Get-Content app/Views/users/show.php
$newSection = @"
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Rôle :</label>
                                        <span class="badge bg-<?php echo ($user[
\role\'] === \Coach\') ? \primary\' : \secondary\'; ?> fs-6">
                                            <?php echo ucfirst($user[\role\'] ?? \Archer\'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Administrateur :</label>
                                        <span class="badge bg-<?php echo ($user[\is_admin\'] ?? $user[\isAdmin\'] ?? false) ? \danger\' : \secondary\'; ?> fs-6">
                                            <?php echo ($user[\is_admin\'] ?? $user[\isAdmin\'] ?? false) ? \Oui\' : \Non\'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Statut :</label>
                                        <span class="badge bg-<?php echo ($user[\is_banned\'] ?? $user[\isBanned\'] ?? false) ? \danger\' : \success\'; ?> fs-6">
                                            <?php echo ($user[\is_banned\'] ?? $user[\isBanned\'] ?? false) ? \Banni\' : \Actif\'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
"@
$content = $content -replace "(?s)<div class=\"row\">.*?<div class=\"col-md-6\">.*?<label class=\"form-label fw-bold\">Statut.*?</div>", $newSection
$content | Set-Content app/Views/users/show.php
