import {internalIpV4} from 'internal-ip';

(async () => {
    const localIp = await internalIpV4();
    console.log(localIp);
})();
