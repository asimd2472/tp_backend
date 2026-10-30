<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            color: #23364d;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
        }

        .header {
            background: #194d88;
            color: #fff;
            padding: 18px 34px;
            width: 100%;
        }

        .brand {
            font-size: 17px;
            font-weight: bold;
            letter-spacing: .5px;
        }

        .brand-note {
            font-size: 9px;
            text-align: right;
        }

        .content {
            padding: 24px 38px 30px;
        }

        h1 {
            color: #244e80;
            font-size: 17px;
            margin: 0 0 3px;
        }

        .booking-meta {
            color: #64748b;
            font-size: 8px;
            margin-bottom: 12px;
        }

        .notice {
            border: 1px solid #244e80;
            color: #244e80;
            font-weight: bold;
            margin-bottom: 14px;
            padding: 8px 10px;
        }

        h2 {
            color: #244e80;
            font-size: 12px;
            margin: 13px 0 5px;
        }

        .details,
        .project-details {
            border-collapse: collapse;
            width: 100%;
        }

        .details td,
        .project-details td {
            border-bottom: 1px solid #cbd5e1;
            padding: 4px 2px;
            vertical-align: top;
        }

        .label {
            color: #64748b;
            width: 29%;
        }

        .value {
            color: #1f2937;
            font-weight: bold;
        }

        .product {
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
            margin: 6px 0;
            page-break-inside: avoid;
            width: 100%;
        }

        .product-image {
            padding: 7px;
            text-align: center;
            vertical-align: top;
            width: 88px;
        }

        .product-image img {
            height: 92px;
            object-fit: contain;
            width: 72px;
        }

        .product-info {
            padding: 7px 9px 7px 2px;
            vertical-align: top;
        }

        .product-name {
            color: #244e80;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .product-info p {
            margin: 1px 0;
        }

        .product-label {
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
            margin-top: 15px;
            padding-top: 7px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="brand">TATA PRAVESH</td>
            <td class="brand-note">Doors &amp; Windows by Tata Steel</td>
        </tr>
    </table>

    <main class="content">
        <h1>Booking &amp; Demo Request: Details Submitted</h1>
        <div class="booking-meta">
            Booking ID: {{ $bookingNumber }}
            &nbsp; | &nbsp;
            Submitted on: {{ $submittedAt->format('d F Y') }}
        </div>

        <div class="notice">
            Our executive will contact you within 24 hours to schedule your demo. No payment is needed now.
        </div>

        <h2>Your details</h2>
        <table class="details">
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ $user->name }}</td>
            </tr>
            <tr>
                <td class="label">Mobile number</td>
                <td class="value">{{ $user->phone ?: 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">Email ID</td>
                <td class="value">{{ $user->email }}</td>
            </tr>
        </table>

        <h2>Delivery address</h2>
        <table class="details">
            <tr>
                <td class="label">Address</td>
                <td class="value">{{ $cartDetail->address }}</td>
            </tr>
            <tr>
                <td class="label">Pincode</td>
                <td class="value">{{ $cartDetail->pincode }}</td>
            </tr>
        </table>

        <h2>Project details</h2>
        <table class="project-details">
            <tr>
                <td class="label">Construction stage</td>
                <td class="value">{{ $cartDetail->construction_stage }}</td>
            </tr>
            <tr>
                <td class="label">Total products ordered</td>
                <td class="value">{{ count($products) }} {{ count($products) === 1 ? 'product' : 'products' }}</td>
            </tr>
        </table>

        <h2>Products selected</h2>
        @foreach ($products as $index => $product)
            <table class="product">
                <tr>
                    <td class="product-image">
                        @if ($product['image_data_uri'])
                            <img src="{{ $product['image_data_uri'] }}" alt="">
                        @endif
                    </td>
                    <td class="product-info">
                        <div class="product-name">{{ $index + 1 }}. {{ $product['productName'] }}</div>
                        <p><span class="product-label">Type:</span> {{ $product['type'] }}</p>
                        <p><span class="product-label">Model:</span> {{ $product['modelName'] }}</p>
                        <p><span class="product-label">Design:</span> {{ $product['designName'] }}</p>
                        <p><span class="product-label">Shade:</span> {{ $product['shadeName'] }}</p>
                        <p><span class="product-label">Size:</span> {{ $product['sizeLabel'] }}</p>
                        <p><span class="product-label">Quantity:</span> {{ $product['quantity'] }}</p>
                    </td>
                </tr>
            </table>
        @endforeach

        <div class="footer">Thank you for choosing TATA PRAVESH.</div>
    </main>
</body>
</html>
