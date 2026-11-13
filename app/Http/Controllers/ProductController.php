<?php

namespace App\Http\Controllers;

use App\Actions\SaveProductName;
use App\Models\ProductName;
use App\Services\ProductCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Trang "Tên hàng": sửa tên tiếng Việt (chữ ERP mất dấu '?'), tên tiếng Trung và quy cách theo mã hàng.
 * Áp dụng cho mọi phiếu giao, đơn hàng và bản in. Chỉ ghi vào MySQL của app; ERP không bị đổi.
 */
class ProductController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request, ProductCatalog $catalog): View
    {
        $rows = $catalog->rows();
        $counts = $catalog->counts($rows);
        $tab = $request->query('tab');
        if (! in_array($tab, [ProductCatalog::TAB_LOST, ProductCatalog::TAB_MANUAL, ProductCatalog::TAB_ALL], true)) {
            // Mặc định mở tab "Còn dấu ?" khi còn mã cần sửa.
            $tab = $counts[ProductCatalog::TAB_LOST] > 0 ? ProductCatalog::TAB_LOST : ProductCatalog::TAB_ALL;
        }
        $q = trim((string) $request->query('q'));

        $filtered = $catalog->filter($rows, $tab, $q);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $products = new LengthAwarePaginator(
            $filtered->forPage($page, self::PER_PAGE)->values(),
            $filtered->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('products.index', compact('products', 'counts', 'tab', 'q'));
    }

    public function update(Request $request, ProductCatalog $catalog, SaveProductName $save): RedirectResponse
    {
        $data = $request->validate([
            'item_code' => ['required', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:255'],
            'name_zh' => ['nullable', 'string', 'max:255'],
            'spec' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'in:save,reset'],
        ]);
        $erp = $catalog->erpData($data['item_code']);
        abort_if($erp === null, 404); // chỉ sửa mã đã có trên phiếu / đơn

        $anchor = '#p-'.substr(md5($data['item_code']), 0, 10);
        if (($data['action'] ?? 'save') === 'reset') {
            ProductName::where('item_code', $data['item_code'])->delete();

            return redirect()->to(url()->previous().$anchor)
                ->with('status', "Mã {$data['item_code']}: đã quay về tên gợi ý tự động từ ERP.");
        }

        $save($data['item_code'], $erp['erp_name'], $erp['erp_spec'], [
            'name' => $data['name'] ?? null,
            'name_zh' => $data['name_zh'] ?? null,
            'spec' => $data['spec'] ?? null,
        ]);

        return redirect()->to(url()->previous().$anchor)
            ->with('status', "Đã lưu mã {$data['item_code']}: áp dụng cho mọi phiếu giao, đơn hàng và bản in.");
    }
}
