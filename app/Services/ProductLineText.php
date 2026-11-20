<?php

namespace App\Services;

use App\Models\ProductName;
use App\Support\ChineseText;
use App\Support\VietnameseAccentRestorer;

/**
 * Tên hàng và quy cách của MỘT dòng hàng, luôn đủ hai thứ tiếng (Việt + Trung) để in trên phiếu và đơn:
 *
 *  Tên hàng  - Việt: danh mục (người nhập) > tên ERP đã khôi phục dấu > (ERP chỉ có chữ Hán) dịch ngược > tên loại thiết bị.
 *              Trung: danh mục > chữ Hán có sẵn trong tên ERP > tên loại thiết bị > "貨品".
 *  Quy cách  - Việt: danh mục > quy cách ERP đã khôi phục dấu (chữ Hán dịch ngược theo bảng từ).
 *              Trung: danh mục > chữ Hán có sẵn trong ERP > dịch theo bảng từ (config/glossary.php).
 *              Quy cách chỉ là mã/model (L3110, SATA3...) không cần dịch nên spec_zh để null: phiếu in mã một lần.
 */
class ProductLineText
{
    private const GENERIC_ZH = '貨品';

    public function __construct(
        private readonly VietnameseAccentRestorer $restorer,
        private readonly ProductTypeClassifier $types,
        private readonly BilingualGlossary $glossary,
    ) {}

    /**
     * @return array{name: string, name_status: string, name_zh: string, name_zh_source: string, name_suggested: string, name_zh_auto: string,
     *               type: array|null, spec: string, spec_zh: ?string, spec_suggested: string, spec_zh_auto: ?string}
     */
    public function build(?string $itemCode, ?string $erpName, ?string $erpSpec, ?ProductName $entry = null): array
    {
        ['latin' => $latin, 'zh' => $erpZh] = ChineseText::split(trim((string) $erpName));
        $suggestedName = $this->restorer->suggest($latin);
        $type = $this->types->classify($itemCode, $suggestedName !== '' ? $suggestedName : $latin);

        if ($suggestedName === '') { // ERP chỉ ghi chữ Hán
            $translated = $erpZh !== '' ? $this->glossary->toVietnamese($erpZh) : '';
            $suggestedName = ($translated !== '' && $translated !== $erpZh) ? $translated : ($type['vi'] ?? '');
        }
        [$zhAuto, $zhSource] = match (true) {
            $erpZh !== '' => [$erpZh, 'erp'],
            $type !== null => [$type['zh'], 'type'],
            default => [self::GENERIC_ZH, 'fallback'],
        };

        [$specVi, $specZh] = $this->spec(trim((string) $erpSpec));

        return [
            'name' => $entry?->name ?? $suggestedName,
            'name_status' => $entry?->name !== null
                ? CustomerNameResolver::STATUS_MANUAL
                : $this->restorer->statusOf($latin, $this->restorer->suggest($latin)),
            'name_zh' => $entry?->name_zh ?? $zhAuto,
            'name_zh_source' => $entry?->name_zh !== null ? 'catalog' : $zhSource,
            'name_suggested' => $suggestedName,
            'name_zh_auto' => $zhAuto,
            'type' => $type,
            'spec' => $entry?->spec ?? $specVi,
            'spec_zh' => $entry?->spec_zh ?? $specZh,
            'spec_suggested' => $specVi,
            'spec_zh_auto' => $specZh,
        ];
    }

    /** @return array{0: string, 1: ?string} [quy cách tiếng Việt, quy cách tiếng Trung hoặc null nếu chỉ là mã] */
    private function spec(string $spec): array
    {
        if ($spec === '') {
            return ['', null];
        }

        if (preg_match('/\p{Han}/u', $spec)) {
            // ERP đã có chữ Hán: dòng Trung giữ nguyên bản gốc, dòng Việt là bản dịch ngược theo bảng từ (bỏ chữ Hán còn lại).
            $vi = ChineseText::split($this->glossary->toVietnamese($spec))['latin'];

            return [$this->restorer->suggest($vi), $spec];
        }

        $vi = $this->restorer->suggest($spec);
        $zh = $this->glossary->toChinese($vi);

        return [$vi, $zh !== $vi ? $zh : null];
    }
}
