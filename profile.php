<?php
    if (!defined('IN_APP')) {
        header('Location: index.php');
        exit;
    }

    $profile_links = [
        'github_url'     => $current_user['github_url'] ?? '',
        'figma_url'      => $current_user['figma_url'] ?? '',
        'api_docs_url'   => $current_user['api_docs_url'] ?? '',
        'report_pdf_url' => $current_user['report_pdf_url'] ?? '',
    ];
    $profile_link_attrs = function (string $key) use ($profile_links) {
        $url = $profile_links[$key];
        return $url ? 'href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener"' : 'href=""';
    };
?>

<div class="profile-content-wrap">
    <div class="profile-main-content">
        <div class="profile-info">
            <div class="profile-info-avt">
                <span><?= htmlspecialchars(name_initials($current_user['full_name'])) ?></span>
            </div>
            <div class="profile-info-txt">
                <span class="profile-info-txt-name"><?= htmlspecialchars($current_user['full_name']) ?></span>
                <span class="profile-info-txt-msv">Mã sinh viên: <?= htmlspecialchars($current_user['student_id']) ?></span>
            </div>
        </div>
        <hr class="profile-hr">
        <div class="profile-assets">
            <h3 class="profile-assets-heading">Tài nguyên dự án</h3>
            <ul class="profile-assets-list">
                <div class="row">
                    <div class="col-6">
                        <li class="profile-assets-item">
                            <a <?= $profile_link_attrs('github_url') ?> class="profile-assets-item-link">
                                <div class="profile-assets-item-avt">
                                    <span>G</span>
                                </div>
                                <div class="profile-assets-item-txt">
                                    <span class="profile-assets-item-txt-name">GitHub</span>
                                    <span class="profile-assets-item-txt-desc">Mã nguồn dự án</span>
                                </div>
                            </a>
                        </li>
                    </div>
                    <div class="col-6">
                        <li class="profile-assets-item">
                            <a <?= $profile_link_attrs('figma_url') ?> class="profile-assets-item-link">
                                <div class="profile-assets-item-avt">
                                    <span>F</span>
                                </div>
                                <div class="profile-assets-item-txt">
                                    <span class="profile-assets-item-txt-name">Figma</span>
                                    <span class="profile-assets-item-txt-desc">Thiết kế giao diện</span>
                                </div>
                            </a>
                        </li>
                    </div>
                    <div class="col-6">
                        <li class="profile-assets-item">
                            <a <?= $profile_link_attrs('api_docs_url') ?> class="profile-assets-item-link">
                                <div class="profile-assets-item-avt">
                                    <span>S</span>
                                </div>
                                <div class="profile-assets-item-txt">
                                    <span class="profile-assets-item-txt-name">Swagger</span>
                                    <span class="profile-assets-item-txt-desc">Tài liệu REST API</span>
                                </div>
                            </a>
                        </li>
                    </div>
                    <div class="col-6">
                        <li class="profile-assets-item">
                            <a <?= $profile_link_attrs('report_pdf_url') ?> class="profile-assets-item-link">
                                <div class="profile-assets-item-avt">
                                    <span>B</span>
                                </div>
                                <div class="profile-assets-item-txt">
                                    <span class="profile-assets-item-txt-name">Báo cáo</span>
                                    <span class="profile-assets-item-txt-desc">Báo cáo hoàn chỉnh (pdf)</span>
                                </div>
                            </a>
                        </li>
                    </div>
                </div>
            </ul>
        </div>
    </div>
</div>

<style>
    .profile-content-wrap {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-main-content {
        background-color: white;
        border-radius: 16px;
        padding: 32px;
        width: 100%;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }
    .profile-info {
        display: flex;
        gap: 24px;
    }
    .profile-info-avt {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 140px;
        height: 140px;
        background-color: rgba(var(--primary-color-highlight), 0.1);
        font-size: 60px;
        color: var(--primary-color);
        border-radius: 100%;
    }
    .profile-info-txt {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .profile-info-txt-name {
        font-weight: bold;
        font-size: 24px;
    }
    .profile-info-txt-msv {
        color: var(--sub-text-color);
    }

    .profile-hr {
        margin: 16px 0;
        border: none;
        border-top: 2px solid #ddd;
    }

    .profile-assets-list {
        list-style: none;
        padding: 0;
    }
    .profile-assets-item {
        background-color: var(--border-color);
        border: 1px solid #ddd;
        margin: 4px 0;
        border-radius: 8px;
        padding: 8px;
    }
    .profile-assets-item-link {
        text-decoration: none;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .profile-assets-item-avt {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white-text-color);
        background-color: var(--primary-color);
        border-radius: 100%;
    }
    .profile-assets-item-txt {
        display: flex;
        flex-direction: column;
    }
    .profile-assets-item-txt-name {
        color: var(--primary-color);
        font-weight: 600;
    }
    .profile-assets-item-txt-desc {
        color: var(--sub-text-color);
    }
</style>