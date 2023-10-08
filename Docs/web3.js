const https = require('https');
const Web3 = require('web3'), web3 = new Web3(new Web3.providers.WebsocketProvider('ws://localhost:7639'));

let latestBlockChecked,
    urls = ['https://exchangeurl.com'];

async function checkLastBlock() {
    let block = await web3.eth.getBlock('latest');
	//let block = await web3.eth.getBlock('0xB35662');
	
    if(latestBlockChecked === block.number) return;
    latestBlockChecked = block.number;

    console.log(`Searching block ${block.number}`);
    web3.eth.getAccounts().then((accounts) => {
        if(block && block.transactions) {
            for(let txHash of block.transactions) {
                web3.eth.getTransaction(txHash).then((tx) => {
                    if(!tx) return;

                    web3.eth.getPastLogs({
                        fromBlock: block.number,
                        toBlock: block.number,
                        address: tx.to
                    }).then((logs) => {
                        logs.forEach((log) => {
                            let contract = web3.eth.abi.decodeParameters([
                                { type: 'address', name: 'from' },
                                { type: 'address', name: 'ignore1' },
                                { type: 'function', name: 'ignore2' },
                                { type: 'address', name: 'to' },
                                { type: 'uint256', name: 'amount' },
                                { type: 'uint256', name: 'ignore3' },
                                { type: 'uint256', name: 'ignore4' }
                            ], log.data.replace('0x', ''));

                            if(accounts.includes(contract.to)) {
                                console.log(`Found transaction ${txHash} / *Internal Transaction* ${contract.to}`);
                                urls.forEach((url) => https.get(`${url}/IPN/ETH/hash/${txHash}`).on('error', console.log));
                            }
                        });
                    });

                    if(accounts.includes(tx.to)) {
                        console.log(`Found transaction ${txHash} / ${tx.from} => ${tx.to}`);
                        urls.forEach((url) => https.get(`${url}/IPN/ETH/hash/${txHash}`).on('error', console.log));
                    }
                });
            }
        }
    });
}

setInterval(checkLastBlock, 1000);

console.log('web3.js started');