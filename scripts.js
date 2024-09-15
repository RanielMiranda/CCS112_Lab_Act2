let lowStockItems = [];

function showLowStockAlerts() {
    lowStockItems.forEach(function(item) {
        alert("Low stock alert: " + item.name + " has only " + item.quantity + " items left!");
    });
}

function editItem(id) {
    console.log('Editing item with ID:', id);
    fetch('Connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `edit=true&id=${id}`
    })
    .then(response => {
        console.log('Raw response:', response);
        return response.json();
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.item) {
            const item = data.item;
            console.log('Item data:', item);
            document.getElementById('itemId').value = item.ID;
            document.getElementById('name').value = item.Name;
            document.getElementById('category').value = item.Category;
            document.getElementById('price').value = item.Price;
            document.getElementById('quantity').value = item.Quantity;
            document.getElementById('submitBtn').value = 'Update Item';
        } else {
            console.error('No item data received');
            console.log('Full response:', data);
        }
        
        // Update the inventory table
        if (data.inventory_table) {
            document.getElementById('inventory-table').innerHTML = data.inventory_table;
        }
        
        // Handle low stock items
        if (data.lowStockItems) {
            lowStockItems = data.lowStockItems;
            showLowStockAlerts();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching the item data. Please try again.');
    });
}

function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        const formData = new FormData();
        formData.append('delete', 'true');
        formData.append('id', id);

        fetch('Connection.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            updateInventory();
        })
        .catch(error => console.error('Error:', error));
    }
}

function updateInventory() {
    fetch('Connection.php', {
        method: 'POST',
        body: new FormData()
    })
    .then(response => response.json())
    .then(data => {
        if (data.inventory_table) {
            document.getElementById('inventory-table').innerHTML = data.inventory_table;
        }
        if (data.lowStockItems) {
            lowStockItems = data.lowStockItems;
            showLowStockAlerts();
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const itemForm = document.getElementById('itemForm');
    const searchForm = document.getElementById('searchForm');
    const inventoryTable = document.getElementById('inventory-table');
    const searchResult = document.getElementById('search-result');

    updateInventory();

    itemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('Connection.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response data:', data);  // Log the entire response
            if (data.message) {
                alert(data.message);
            }
            if (data.inventory_table) {
                inventoryTable.innerHTML = data.inventory_table;
            }
            if (data.debug_info) {
                console.log('Debug info:', data.debug_info);
            }
            this.reset();
            document.getElementById('itemId').value = '';
            document.getElementById('submitBtn').value = 'Add Item';
            updateInventory();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('Connection.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Search response:', data);
            if (data.message) {
                alert(data.message);
            }
            if (data.search_results) {
                displaySearchResults(data.search_results);
            }
            if (data.inventory_table) {
                inventoryTable.innerHTML = data.inventory_table;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during the search. Please try again.');
        });
    });
});

function displaySearchResults(results) {
    const searchResult = document.getElementById('search-result');
    if (results.length === 0) {
        searchResult.innerHTML = '<p>No results found.</p>';
        return;
    }

    let html = '<table class="inventory-table">';
    html += '<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Quantity</th><th>Actions</th></tr></thead>';
    html += '<tbody>';

    results.forEach(item => {
        html += `<tr>
            <td>${item.ID}</td>
            <td>${item.Name}</td>
            <td>${item.Category}</td>
            <td>$${parseFloat(item.Price).toFixed(2)}</td>
            <td>${item.Quantity}</td>
            <td>
                <button class='edit-btn' onclick='editItem(${item.ID})'>Edit</button>
                <button class='delete-btn' onclick='deleteItem(${item.ID})'>Delete</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    searchResult.innerHTML = html;
}

// ... (keep other existing functions)
