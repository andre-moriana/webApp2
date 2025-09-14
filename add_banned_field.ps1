$content = Get-Content app/Views/users/show.php
$newRow = @"
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Statut :</label>
                                        <span class="badge bg-<?php echo ($user[
\is_banned\'] ?? $user[\isBanned\'] ?? false) ? \danger\' : \success\'; ?> fs-6">
                                            <?php echo ($user[\is_banned\'] ?? $user[\isBanned\'] ?? false) ? \Banni\' : \Actif\'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
"@
$content = $content -replace "</div>\s*</div>\s*</div>\s*</div>\s*<!-- Informations sportives -->", "$newRow`n                        </div>`n                    </div>`n                </div>`n                `n                <!-- Informations sportives -->"
$content | Set-Content app/Views/users/show.php
