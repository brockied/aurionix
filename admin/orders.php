<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? 0)) {
    header('Location: index.php');
    exit;
}

$pdo = get_db();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['payment_status'] ?? '';
    
    if ($orderId && in_array($newStatus, ['pending', 'paid', 'failed', 'refunded'])) {
        $stmt = $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        $message = 'Order status updated successfully!';
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_orders'] ?? [];
    $message = '';
    
    if (!empty($selected)) {
        switch ($action) {
            case 'mark_paid':
                $stmt = $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id IN (' . str_repeat('?,', count($selected) - 1) . '?)');
                $stmt->execute($selected);
                $message = count($selected) . ' order(s) marked as paid!';
                break;
                
            case 'mark_pending':
                $stmt = $pdo->prepare('UPDATE orders SET payment_status = "pending" WHERE id IN (' . str_repeat('?,', count($selected) - 1) . '?)');
                $stmt->execute($selected);
                $message = count($selected) . ' order(s) marked as pending!';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare('DELETE FROM orders WHERE id IN (' . str_repeat('?,', count($selected) - 1) . '?)');
                $stmt->execute($selected);
                $message = count($selected) . ' order(s) deleted successfully!';
                break;
        }
    }
}

// Filter and search parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date_range'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? $search : 0;
}

if ($statusFilter !== 'all') {
    $whereConditions[] = "o.payment_status = ?";
    $params[] = $statusFilter;
}

switch ($dateFilter) {
    case 'today':
        $whereConditions[] = "DATE(o.created_at) = CURDATE()";
        break;
    case 'week':
        $whereConditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereConditions[] = "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Sort options
switch ($sort) {
    case 'newest':
        $orderBy = 'ORDER BY o.created_at DESC';
        break;
    case 'oldest':
        $orderBy = 'ORDER BY o.created_at ASC';
        break;
    case 'amount_high':
        $orderBy = 'ORDER BY o.total DESC';
        break;
    case 'amount_low':
        $orderBy = 'ORDER BY o.total ASC';
        break;
    case 'customer':
        $orderBy = 'ORDER BY u.username ASC';
        break;
    default:
        $orderBy = 'ORDER BY o.created_at DESC';
}

// Get orders with details
$query = "
    SELECT 
        o.*,
        u.username,
        u.email,
        COUNT(oi.id) as item_count,
        GROUP_CONCAT(t.title SEPARATOR ', ') as track_titles
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN tracks t ON oi.track_id = t.id
    $whereClause
    GROUP BY o.id
    $orderBy
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get summary stats
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = "paid"')->fetchColumn();
$pendingOrders = $pdo->query('SELECT COUNT(*) FROM orders WHERE payment_status = "pending"')->fetchColumn();
$todayOrders = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$todayRevenue = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = "paid"')->fetchColumn();

// Revenue by status
$revenueByStatus = $pdo->query('
    SELECT 
        payment_status, 
        COUNT(*) as order_count, 
        COALESCE(SUM(total), 0) as total_amount 
    FROM orders 
    GROUP BY payment_status
')->fetchAll();

// Top customers
$topCustomers = $pdo->query('
    SELECT 
        u.username,
        u.email,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = "paid"
    GROUP BY u.id
    HAVING order_count > 0
    ORDER BY total_spent DESC
    LIMIT 5
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orders - Aurionix Admin</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <link rel="stylesheet" href="/assets/css/admin.css" />
</head>
<body>
  <!-- Enhanced Admin Header -->
  <header class="admin-header">
    <div class="navbar__logo">
      <a href="/admin/dashboard.php">
        <span class="logo-text">Aurionix Admin</span>
      </a>
    </div>
    <nav class="navbar__links">
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="albums.php">Albums</a></li>
        <li><a href="tracks.php">Tracks</a></li>
        <li><a href="orders.php" class="active">Orders</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="/logout.php">Logout</a></li>
      </ul>
    </nav>
    <div class="admin-user">
      <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
      <span><?= htmlspecialchars($_SESSION['username']); ?></span>
    </div>
  </header>

  <main class="admin-container">
    <div class="page-header">
      <div>
        <h1>Orders</h1>
        <p class="page-subtitle">Manage customer orders and track sales performance</p>
      </div>
      <div style="display: flex; gap: 1rem;">
        <button onclick="exportOrders()" class="btn btn--outline btn--lg">
          📊 Export Data
        </button>
        <button onclick="refreshOrders()" class="btn btn--secondary btn--lg">
          🔄 Refresh
        </button>
      </div>
    </div>

    <!-- Success Message -->
    <?php if (isset($message)): ?>
      <div class="alert alert-success">
        <span>✅</span>
        <span><?= htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
      <div class="stats-card">
        <div class="stats-card-icon primary">🛒</div>
        <h3>Total Orders</h3>
        <div class="stats-number"><?= number_format($totalOrders); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon success">💰</div>
        <h3>Total Revenue</h3>
        <div class="stats-number"><?= format_price((float)$totalRevenue); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon warning">⏳</div>
        <h3>Pending Orders</h3>
        <div class="stats-number"><?= number_format($pendingOrders); ?></div>
      </div>

      <div class="stats-card">
        <div class="stats-card-icon secondary">📈</div>
        <h3>Today's Orders</h3>
        <div class="stats-number"><?= number_format($todayOrders); ?></div>
        <div class="stats-change positive">
          <span><?= format_price((float)$todayRevenue); ?> revenue</span>
        </div>
      </div>
    </div>

    <!-- Revenue Breakdown -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
      <!-- Revenue by Status -->
      <div class="admin-table-container">
        <div class="admin-table-header">
          <h3 class="admin-table-title">Revenue Breakdown</h3>
        </div>
        <div style="padding: 1.5rem;">
          <div class="revenue-breakdown">
            <?php foreach ($revenueByStatus as $status): ?>
              <div class="revenue-item">
                <div class="revenue-status <?= $status['payment_status']; ?>">
                  <?php
                  $statusIcons = [
                    'paid' => '✅',
                    'pending' => '⏳',
                    'failed' => '❌',
                    'refunded' => '↩️'
                  ];
                  echo $statusIcons[$status['payment_status']] ?? '📦';
                  ?>
                  <?= ucfirst($status['payment_status']); ?>
                </div>
                <div class="revenue-details">
                  <div class="revenue-amount"><?= format_price((float)$status['total_amount']); ?></div>
                  <div class="revenue-count"><?= number_format($status['order_count']); ?> orders</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Top Customers -->
      <div class="admin-table-container">
        <div class="admin-table-header">
          <h3 class="admin-table-title">Top Customers</h3>
        </div>
        <div style="padding: 1.5rem;">
          <?php if (!empty($topCustomers)): ?>
            <div class="top-customers">
              <?php foreach ($topCustomers as $index => $customer): ?>
                <div class="customer-item">
                  <div class="customer-rank">#<?= $index + 1; ?></div>
                  <div class="customer-info">
                    <div class="customer-name"><?= htmlspecialchars($customer['username']); ?></div>
                    <div class="customer-email"><?= htmlspecialchars($customer['email']); ?></div>
                  </div>
                  <div class="customer-stats">
                    <div class="customer-spent"><?= format_price((float)$customer['total_spent']); ?></div>
                    <div class="customer-orders"><?= $customer['order_count']; ?> orders</div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p style="text-align: center; color: var(--admin-text-muted);">No customers yet</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="admin-table-container" style="margin-bottom: 2rem;">
      <div class="admin-table-header">
        <h3 class="admin-table-title">Filter & Search Orders</h3>
      </div>
      
      <div style="padding: 1.5rem;">
        <form method="get" action="orders.php" class="filter-form">
          <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-field" style="margin: 0;">
              <label for="search">Search Orders</label>
              <input type="text" id="search" name="search" value="<?= htmlspecialchars($search); ?>" 
                     placeholder="Search by order ID, customer name, or email..." />
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="status">Status</label>
              <select id="status" name="status">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : ''; ?>>✅ Paid</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : ''; ?>>❌ Failed</option>
                <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : ''; ?>>↩️ Refunded</option>
              </select>
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="date_range">Date Range</label>
              <select id="date_range" name="date_range">
                <option value="all" <?= $dateFilter === 'all' ? 'selected' : ''; ?>>All Time</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="week" <?= $dateFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="month" <?= $dateFilter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
              </select>
            </div>
            
            <div class="form-field" style="margin: 0;">
              <label for="sort">Sort by</label>
              <select id="sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="amount_high" <?= $sort === 'amount_high' ? 'selected' : ''; ?>>Highest Amount</option>
                <option value="amount_low" <?= $sort === 'amount_low' ? 'selected' : ''; ?>>Lowest Amount</option>
                <option value="customer" <?= $sort === 'customer' ? 'selected' : ''; ?>>Customer Name</option>
              </select>
            </div>
            
            <button type="submit" class="btn btn--primary">🔍 Search</button>
          </div>
        </form>
        
        <?php if ($search || $statusFilter !== 'all' || $dateFilter !== 'all'): ?>
          <div style="margin-top: 1rem;">
            <a href="orders.php" class="btn btn--outline btn--sm">❌ Clear Filters</a>
            <span style="color: var(--admin-text-muted); margin-left: 1rem;">
              Showing <?= count($orders); ?> of <?= $totalOrders; ?> orders
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Orders Table -->
    <form method="post" action="orders.php" id="bulk-form">
      <div class="admin-table-container">
        <div class="admin-table-header">
          <div style="display: flex; align-items: center; gap: 1rem;">
            <h3 class="admin-table-title">Customer Orders</h3>
            <div class="bulk-actions" style="display: none;">
              <select name="bulk_action" class="bulk-action-select" style="padding: 0.5rem;">
                <option value="">Bulk Actions</option>
                <option value="mark_paid">Mark as Paid</option>
                <option value="mark_pending">Mark as Pending</option>
                <option value="delete" style="color: var(--admin-error);">Delete Selected</option>
              </select>
              <button type="submit" class="btn btn--secondary btn--sm" onclick="return confirmBulkAction();">
                Apply
              </button>
            </div>
          </div>
          
          <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="toggleSelectAll()" class="btn btn--outline btn--sm">
              Select All
            </button>
            <span class="selected-count" style="color: var(--admin-text-muted); font-size: 0.875rem; align-self: center;">
              0 selected
            </span>
          </div>
        </div>

        <?php if (empty($orders)): ?>
          <div style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🛒</div>
            <h3>No Orders Found</h3>
            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
              <?php if ($search || $statusFilter !== 'all' || $dateFilter !== 'all'): ?>
                No orders match your search criteria. Try adjusting your filters.
              <?php else: ?>
                You haven't received any orders yet. Orders will appear here when customers make purchases.
              <?php endif; ?>
            </p>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th width="40">
                  <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes(this)" />
                </th>
                <th width="80">Order ID</th>
                <th>Customer</th>
                <th>Items</th>
                <th width="100">Amount</th>
                <th width="120">Status</th>
                <th width="100">Payment</th>
                <th width="120">Date</th>
                <th width="150">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
              <tr>
                <td>
                  <input type="checkbox" name="selected_orders[]" value="<?= $order['id']; ?>" 
                         class="order-checkbox" onchange="updateBulkActions()" />
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--admin-primary);">
                    #<?= $order['id']; ?>
                  </div>
                </td>
                <td>
                  <div>
                    <div style="font-weight: 500;">
                      <?= htmlspecialchars($order['username'] ?: 'Guest'); ?>
                    </div>
                    <?php if ($order['email']): ?>
                      <div style="font-size: 0.875rem; color: var(--admin-text-muted);">
                        <?= htmlspecialchars($order['email']); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div>
                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                      <?= $order['item_count']; ?> item<?= $order['item_count'] != 1 ? 's' : ''; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); line-height: 1.3;">
                      <?= htmlspecialchars(substr($order['track_titles'], 0, 50)); ?>
                      <?= strlen($order['track_titles']) > 50 ? '...' : ''; ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-weight: 600; font-size: 1.1rem;">
                    <?= format_price((float)$order['total']); ?>
                  </div>
                </td>
                <td>
                  <span class="status-badge <?= $order['payment_status']; ?>">
                    <?php
                    $statusConfig = [
                      'paid' => ['✅', 'Paid', 'success'],
                      'pending' => ['⏳', 'Pending', 'warning'],
                      'failed' => ['❌', 'Failed', 'error'], 
                      'refunded' => ['↩️', 'Refunded', 'secondary']
                    ];
                    $config = $statusConfig[$order['payment_status']] ?? ['📦', ucfirst($order['payment_status']), 'inactive'];
                    echo $config[0] . ' ' . $config[1];
                    ?>
                  </span>
                </td>
                <td>
                  <div style="font-size: 0.875rem;">
                    <?= htmlspecialchars(ucfirst($order['payment_method'] ?: 'N/A')); ?>
                  </div>
                </td>
                <td>
                  <div style="font-size: 0.875rem;">
                    <div><?= date('M j, Y', strtotime($order['created_at'])); ?></div>
                    <div style="color: var(--admin-text-muted); font-size: 0.75rem;">
                      <?= date('H:i', strtotime($order['created_at'])); ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                    <button type="button" onclick="viewOrderDetails(<?= $order['id']; ?>)" 
                            class="btn btn--primary btn--sm" title="View Details">
                      👁️
                    </button>
                    <div class="dropdown" style="position: relative;">
                      <button type="button" onclick="toggleStatusDropdown(<?= $order['id']; ?>)" 
                              class="btn btn--outline btn--sm" title="Change Status">
                        📝
                      </button>
                      <div class="status-dropdown" id="status-dropdown-<?= $order['id']; ?>" style="display: none;">
                        <form method="post" style="margin: 0;">
                          <input type="hidden" name="order_id" value="<?= $order['id']; ?>" />
                          <button type="submit" name="update_status" value="paid" class="status-option paid">✅ Paid</button>
                          <button type="submit" name="update_status" value="pending" class="status-option pending">⏳ Pending</button>
                          <button type="submit" name="update_status" value="failed" class="status-option failed">❌ Failed</button>
                          <button type="submit" name="update_status" value="refunded" class="status-option refunded">↩️ Refunded</button>
                          <input type="hidden" name="payment_status" value="" />
                        </form>
                      </div>
                    </div>
                    <?php if ($order['email']): ?>
                      <a href="mailto:<?= htmlspecialchars($order['email']); ?>?subject=Order #<?= $order['id']; ?>" 
                         class="btn btn--secondary btn--sm" title="Email Customer">
                        ✉️
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </form>

    <?php if (!empty($orders)): ?>
      <div style="text-align: center; margin-top: 2rem; color: var(--admin-text-muted);">
        Showing all <?= count($orders); ?> order(s)
      </div>
    <?php endif; ?>
  </main>

  <!-- Order Details Modal -->
  <div id="order-modal" class="order-modal" style="display: none;">
    <div class="order-modal-content">
      <div class="order-modal-header">
        <h3 id="order-modal-title">Order Details</h3>
        <button onclick="closeOrderModal()" class="btn btn--outline btn--sm">✕</button>
      </div>
      <div id="order-modal-body" class="order-modal-body">
        Loading...
      </div>
    </div>
  </div>

  <script>
    // Order details modal
    function viewOrderDetails(orderId) {
      const modal = document.getElementById('order-modal');
      const title = document.getElementById('order-modal-title');
      const body = document.getElementById('order-modal-body');
      
      title.textContent = `Order #${orderId} Details`;
      body.innerHTML = 'Loading order details...';
      modal.style.display = 'flex';
      
      // Here you would fetch order details via AJAX
      // For now, we'll show a placeholder
      setTimeout(() => {
        body.innerHTML = `
          <div class="order-details">
            <p><strong>Order ID:</strong> #${orderId}</p>
            <p><strong>Status:</strong> Processing...</p>
            <p><em>Detailed order information would be loaded here via AJAX.</em></p>
          </div>
        `;
      }, 500);
    }

    function closeOrderModal() {
      document.getElementById('order-modal').style.display = 'none';
    }

    // Status dropdown toggle
    function toggleStatusDropdown(orderId) {
      const dropdown = document.getElementById(`status-dropdown-${orderId}`);
      const isVisible = dropdown.style.display === 'block';
      
      // Close all dropdowns first
      document.querySelectorAll('.status-dropdown').forEach(d => {
        d.style.display = 'none';
      });
      
      // Toggle current dropdown
      dropdown.style.display = isVisible ? 'none' : 'block';
    }

    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.status-dropdown').forEach(d => {
          d.style.display = 'none';
        });
      }
    });

    // Export functionality
    function exportOrders() {
      const params = new URLSearchParams(window.location.search);
      params.set('export', 'csv');
      window.open(`orders.php?${params.toString()}`, '_blank');
    }

    function refreshOrders() {
      window.location.reload();
    }

    // Bulk actions functionality
    function toggleAllCheckboxes(selectAllCheckbox) {
      const checkboxes = document.querySelectorAll('.order-checkbox');
      checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
      });
      updateBulkActions();
    }

    function toggleSelectAll() {
      const selectAllCheckbox = document.getElementById('select-all');
      selectAllCheckbox.checked = !selectAllCheckbox.checked;
      toggleAllCheckboxes(selectAllCheckbox);
    }

    function updateBulkActions() {
      const checkboxes = document.querySelectorAll('.order-checkbox:checked');
      const bulkActions = document.querySelector('.bulk-actions');
      const selectedCount = document.querySelector('.selected-count');
      
      if (checkboxes.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = `${checkboxes.length} selected`;
      } else {
        bulkActions.style.display = 'none';
        selectedCount.textContent = '0 selected';
      }
      
      // Update select all checkbox state
      const allCheckboxes = document.querySelectorAll('.order-checkbox');
      const selectAllCheckbox = document.getElementById('select-all');
      
      if (checkboxes.length === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
      } else if (checkboxes.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
      } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
      }
    }

    function confirmBulkAction() {
      const action = document.querySelector('.bulk-action-select').value;
      const checkboxes = document.querySelectorAll('.order-checkbox:checked');
      
      if (!action) {
        alert('Please select an action.');
        return false;
      }
      
      if (checkboxes.length === 0) {
        alert('Please select at least one order.');
        return false;
      }
      
      let message = '';
      switch (action) {
        case 'mark_paid':
          message = `Mark ${checkboxes.length} order(s) as paid?`;
          break;
        case 'mark_pending':
          message = `Mark ${checkboxes.length} order(s) as pending?`;
          break;
        case 'delete':
          message = `Delete ${checkboxes.length} order(s)? This action cannot be undone!`;
          break;
      }
      
      return confirm(message);
    }

    // Auto-submit search form on filter/sort change
    ['status', 'date_range', 'sort'].forEach(id => {
      document.getElementById(id).addEventListener('change', function() {
        document.querySelector('.filter-form').submit();
      });
    });

    // Search input debounce
    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        if (this.value.length >= 2 || this.value.length === 0) {
          document.querySelector('.filter-form').submit();
        }
      }, 500);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
      const modal = document.getElementById('order-modal');
      if (e.target === modal) {
        closeOrderModal();
      }
    });

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      updateBulkActions();
    });
  </script>

  <style>
    /* Revenue Breakdown */
    .revenue-breakdown {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .revenue-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: var(--admin-bg-secondary);
      border-radius: var(--admin-border-radius);
      transition: all 0.2s ease;
    }

    .revenue-item:hover {
      background: rgba(99, 102, 241, 0.05);
    }

    .revenue-status {
      font-weight: 600;
      font-size: 0.875rem;
    }

    .revenue-details {
      text-align: right;
    }

    .revenue-amount {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--admin-text);
    }

    .revenue-count {
      font-size: 0.75rem;
      color: var(--admin-text-muted);
    }

    /* Top Customers */
    .top-customers {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .customer-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: var(--admin-bg-secondary);
      border-radius: var(--admin-border-radius);
    }

    .customer-rank {
      font-weight: 700;
      color: var(--admin-primary);
      min-width: 1.5rem;
    }

    .customer-info {
      flex: 1;
      min-width: 0;
    }

    .customer-name {
      font-weight: 600;
      font-size: 0.9rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .customer-email {
      font-size: 0.75rem;
      color: var(--admin-text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .customer-stats {
      text-align: right;
    }

    .customer-spent {
      font-weight: 600;
      color: var(--admin-success);
      font-size: 0.9rem;
    }

    .customer-orders {
      font-size: 0.75rem;
      color: var(--admin-text-muted);
    }

    /* Status badges */
    .status-badge.paid {
      background: rgba(16, 185, 129, 0.1);
      color: var(--admin-success);
    }

    .status-badge.pending {
      background: rgba(245, 158, 11, 0.1);
      color: var(--admin-warning);
    }

    .status-badge.failed {
      background: rgba(239, 68, 68, 0.1);
      color: var(--admin-error);
    }

    .status-badge.refunded {
      background: rgba(139, 92, 246, 0.1);
      color: var(--admin-secondary);
    }

    /* Status dropdown */
    .status-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: var(--admin-bg-card);
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-border-radius);
      box-shadow: var(--admin-shadow-lg);
      z-index: 1000;
      min-width: 120px;
      backdrop-filter: blur(20px);
    }

    .status-option {
      width: 100%;
      padding: 0.5rem 0.75rem;
      border: none;
      background: none;
      color: var(--admin-text);
      text-align: left;
      cursor: pointer;
      font-size: 0.875rem;
      transition: background-color 0.2s ease;
    }

    .status-option:hover {
      background: rgba(99, 102, 241, 0.1);
    }

    .status-option:first-child {
      border-top-left-radius: var(--admin-border-radius);
      border-top-right-radius: var(--admin-border-radius);
    }

    .status-option:last-child {
      border-bottom-left-radius: var(--admin-border-radius);
      border-bottom-right-radius: var(--admin-border-radius);
    }

    /* Order Modal */
    .order-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
    }

    .order-modal-content {
      background: var(--admin-bg-card);
      border-radius: var(--admin-border-radius-lg);
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      border: 1px solid var(--admin-border);
      overflow: hidden;
    }

    .order-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid var(--admin-border);
    }

    .order-modal-header h3 {
      margin: 0;
      color: var(--admin-text);
    }

    .order-modal-body {
      padding: 1.5rem;
      max-height: 60vh;
      overflow-y: auto;
    }

    /* Bulk actions */
    .bulk-actions {
      align-items: center;
      gap: 0.5rem;
    }
    
    .bulk-action-select {
      background: var(--admin-bg-secondary);
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-border-radius);
      color: var(--admin-text);
      font-size: 0.875rem;
    }

    /* Responsive design */
    @media (max-width: 1200px) {
      .filter-form > div {
        grid-template-columns: 1fr 1fr auto;
      }
      
      .filter-form .form-field:nth-child(3),
      .filter-form .form-field:nth-child(4) {
        grid-column: span 2;
      }
      
      div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 768px) {
      .filter-form > div {
        grid-template-columns: 1fr;
      }
      
      .admin-table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
      }
      
      .bulk-actions {
        justify-content: space-between;
      }

      .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
      }

      .page-header > div:last-child {
        display: flex;
        gap: 0.5rem;
      }
    }
  </style>
</body>
</html>