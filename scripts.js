let lowStockItems = [];

function showLowStockAlerts() {
    lowStockItems.forEach(function(item) {
        alert("Low stock alert: " + item.name + " has only " + item.quantity + " items left!");
    });
}

function editItem(id) {
    fetch('Connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `edit=true&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.item) {
            const item = data.item;
            document.getElementById('itemId').value = item.ID;
            document.getElementById('name').value = item.Name;
            document.getElementById('category').value = item.Category;
            document.getElementById('price').value = item.Price;
            document.getElementById('quantity').value = item.Quantity;
            document.getElementById('submitBtn').value = 'Update Item';
        }
    })
    .catch(error => console.error('Error:', error));
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
    const inventoryTable = document.getElementById('inventory-table');

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
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
});
